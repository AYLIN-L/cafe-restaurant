<?php

use App\Models\User;
use Carbon\CarbonImmutable;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('affiche la page contact publique', function () {
    $response = $this->get('/contact');

    $response->assertOk();
    $response->assertSeeText('Contactez-nous');
    $response->assertSeeText('Nous trouver');
    $response->assertSeeText('248 Boulevard Sour Jdid');
    $response->assertSeeText('Casablanca');
    $response->assertSee('maps?q=', false);
    $response->assertSeeText('Itinéraire');
});

it('permet à un visiteur non connecté d’envoyer un message', function () {
    $response = $this->from('/contact')->post('/contact', [
        'name' => 'Camille Martin',
        'email' => 'camille@example.com',
        'subject' => 'Demande d’information',
        'message' => 'Bonjour, je souhaite connaître vos horaires.',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('contact_messages', [
        'user_id' => null,
        'name' => 'Camille Martin',
        'email' => 'camille@example.com',
        'subject' => 'Demande d’information',
    ]);
});

it('utilise le nom et l’e-mail du compte pour un client connecté', function () {
    $user = User::factory()->create([
        'name' => 'Amina Client',
        'email' => 'amina@example.com',
    ]);

    $response = $this->actingAs($user)->from('/contact')->post('/contact', [
        'name' => 'Usurpateur',
        'email' => 'usurpateur@example.com',
        'subject' => 'Message connecté',
        'message' => 'Ce message doit être associé au compte.',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('contact_messages', [
        'user_id' => $user->id,
        'name' => 'Amina Client',
        'email' => 'amina@example.com',
        'subject' => 'Message connecté',
    ]);

    $this->assertDatabaseMissing('contact_messages', [
        'name' => 'Usurpateur',
        'email' => 'usurpateur@example.com',
    ]);
});

it('préremplit les champs nom et e-mail pour un client connecté', function () {
    $user = User::factory()->create([
        'name' => 'Amina Client',
        'email' => 'amina@example.com',
    ]);

    $response = $this->actingAs($user)->get('/contact');

    $response->assertOk();
    $response->assertSee('value="Amina Client"', false);
    $response->assertSee('value="amina@example.com"', false);
    $response->assertSeeText('Renseigné depuis votre compte.');
});

it('conserve les champs saisis et affiche des erreurs en français', function () {
    $response = $this->from('/contact')->post('/contact', [
        'name' => 'Camille Martin',
        'email' => 'adresse-invalide',
        'subject' => '',
        'message' => '',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHasErrors(['email', 'subject', 'message']);
    $response->assertSessionHasInput('name', 'Camille Martin');
    $response->assertSessionHasInput('email', 'adresse-invalide');

    expect(session('errors')->first('email'))
        ->toBe('Veuillez saisir une adresse e-mail valide.');

    expect(session('errors')->first('subject'))
        ->toBe('Veuillez indiquer le sujet de votre message.');
});

it('affiche le statut ouvert pendant les horaires configurés', function () {
    config(['app.timezone' => 'Africa/Casablanca']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-26 09:00:00', 'Africa/Casablanca'));

    $response = $this->get('/contact');

    $response->assertOk();
    $response->assertSeeText('Ouvert');
    $response->assertSeeText('Ferme à 22h');
});

it('affiche le statut fermé et la prochaine ouverture après 22h', function () {
    config(['app.timezone' => 'Africa/Casablanca']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-26 22:30:00', 'Africa/Casablanca'));

    $response = $this->get('/contact');

    $response->assertOk();
    $response->assertSeeText('Fermé');
    $response->assertSeeText('Ouvre demain à 8h');
});

it('calcule la prochaine ouverture lorsqu’un jour de fermeture est configuré', function () {
    config([
        'restaurant.timezone' => 'Africa/Casablanca',
        'restaurant.closed_days' => ['monday'],
    ]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-26 09:00:00', 'Africa/Casablanca'));

    $response = $this->get('/contact');

    $response->assertOk();
    $response->assertSeeText('Fermé');
    $response->assertSeeText('Ouvre demain à 8h');
});
