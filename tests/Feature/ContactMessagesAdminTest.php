<?php

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Tests de la gestion des messages de contact (administration) et de la
 * notification « Messages » de l'en-tête.
 *
 * Compteur de notification : les messages PAS ENCORE TRAITÉS (statut
 * « unread » ou « read »), même logique que le composer de AppServiceProvider.
 */
function createContactMessage(array $overrides = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'Camille Martin',
        'email' => 'camille@example.com',
        'subject' => 'Demande d’information',
        'message' => 'Bonjour, je souhaite connaître vos horaires d’ouverture.',
        'status' => ContactMessage::STATUS_UNREAD,
    ], $overrides));
}

it('enregistre le statut « unread » à la soumission du formulaire public', function () {
    $response = $this->from('/contact')->post('/contact', [
        'name' => 'Camille Martin',
        'email' => 'camille@example.com',
        'subject' => 'Demande d’information',
        'message' => 'Bonjour, je souhaite connaître vos horaires.',
    ]);

    $response->assertRedirect('/contact')->assertSessionHas('success');

    $this->assertDatabaseHas('contact_messages', [
        'name' => 'Camille Martin',
        'subject' => 'Demande d’information',
        'status' => ContactMessage::STATUS_UNREAD,
    ]);
});

it('affiche la liste des messages et le compteur pour un administrateur', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    createContactMessage();
    createContactMessage(['name' => 'Lucas Bernard', 'email' => 'lucas@example.com', 'subject' => 'Réservation de groupe']);

    $response = $this->actingAs($admin)->get(route('admin.contacts.index'));

    $response->assertOk()
        // Les messages apparaissent dans la liste…
        ->assertSee('Demande d’information')
        ->assertSee('Camille Martin')
        ->assertSee('Lucas Bernard')
        // … et la notification de l'en-tête compte les 2 messages à traiter.
        ->assertSee('aria-label="2 messages de contact pas encore traités"', false)
        ->assertSee(route('admin.contacts.index'), false);
});

it('affiche le badge sur les autres pages d’administration', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    createContactMessage();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('1 message de contact à traiter', false);
});

it('interdit l’accès aux clients et aux visiteurs', function () {
    $client = User::factory()->create(['role' => 'client']);
    $message = createContactMessage();

    // Client connecté : 403 (AdminMiddleware).
    $this->actingAs($client)->get(route('admin.contacts.index'))->assertStatus(403);
    $this->actingAs($client)->get(route('admin.contacts.show', $message))->assertStatus(403);
    $this->actingAs($client)->patch(route('admin.contacts.status', $message), ['status' => 'processed'])
        ->assertStatus(403);
    $this->actingAs($client)->delete(route('admin.contacts.destroy', $message))->assertStatus(403);
});

it('redirige les visiteurs non connectés vers la connexion', function () {
    createContactMessage();

    $this->get(route('admin.contacts.index'))->assertRedirect(route('login'));
});

it('passe automatiquement un message non lu à « lu » à son ouverture', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $message = createContactMessage();

    $this->actingAs($admin)->get(route('admin.contacts.show', $message))->assertOk()->assertSee('vos horaires');

    expect($message->fresh()->status)->toBe(ContactMessage::STATUS_READ);

    // Le message marqué « lu » reste compté comme à traiter (pas encore traité).
    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('aria-label="1 message de contact pas encore traité"', false);
});

it('retire le message de la notification quand il est traité', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $message = createContactMessage();

    $response = $this->actingAs($admin)->patch(route('admin.contacts.status', $message), [
        'status' => ContactMessage::STATUS_PROCESSED,
    ]);

    $response->assertRedirect()->assertSessionHas('success');
    expect($message->fresh()->status)->toBe(ContactMessage::STATUS_PROCESSED);

    // Compteur à zéro : aucun message pas encore traité.
    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('aria-label="0 message de contact pas encore traité"', false);
});

it('refuse un statut invalide', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $message = createContactMessage();

    $this->actingAs($admin)
        ->from(route('admin.contacts.index'))
        ->patch(route('admin.contacts.status', $message), ['status' => 'nimporte-quoi'])
        ->assertSessionHasErrors('status');

    expect($message->fresh()->status)->toBe(ContactMessage::STATUS_UNREAD);
});

it('permet de filtrer par statut et de rechercher', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unread = createContactMessage(['subject' => 'Question sur le menu']);
    $processed = createContactMessage([
        'name' => 'Lucas Bernard',
        'email' => 'lucas@example.com',
        'subject' => 'Réservation de groupe',
        'message' => 'Nous serons quinze personnes samedi prochain.',
        'status' => ContactMessage::STATUS_PROCESSED,
    ]);

    // Filtre par statut.
    $this->actingAs($admin)->get(route('admin.contacts.index', ['status' => 'unread']))
        ->assertOk()
        ->assertSee('Question sur le menu')
        ->assertDontSee('Réservation de groupe');

    // Recherche dans le contenu.
    $this->actingAs($admin)->get(route('admin.contacts.index', ['search' => 'horaires']))
        ->assertOk()
        ->assertSee('Camille Martin')
        ->assertDontSee('Lucas Bernard');

    // Les liens de pagination conservent la recherche.
    $response = $this->actingAs($admin)->get(route('admin.contacts.index', ['search' => 'horaires']));
    $response->assertOk();
    expect($response->getContent())->toContain('search=horaires');
});

it('pagine la liste et conserve les paramètres', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (range(1, 13) as $index) {
        createContactMessage(['subject' => 'Message numéro '.$index]);
    }

    // 12 éléments par page : le 13e n'apparaît que sur la page 2.
    $this->actingAs($admin)->get(route('admin.contacts.index'))->assertOk()->assertDontSee('Message numéro 13');
    $this->actingAs($admin)->get(route('admin.contacts.index', ['page' => 2]))->assertOk()->assertSee('Message numéro 13');

    $response = $this->actingAs($admin)->get(route('admin.contacts.index', ['status' => 'unread']));
    expect($response->getContent())->toContain('status=unread&amp;page=2');
});

it('supprime définitivement un message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $message = createContactMessage();

    $this->actingAs($admin)->delete(route('admin.contacts.destroy', $message))
        ->assertRedirect(route('admin.contacts.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($message);
});

it('affiche un état vide quand aucun message n’existe', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.contacts.index'))
        ->assertOk()
        ->assertSee('Aucun message pour le moment');
});
