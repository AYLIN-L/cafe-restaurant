<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Page admin des réservations : légende, menu à trois points et actions
 * proposées selon l'état de chaque réservation.
 *
 * Les libellés du menu sont cherchés sous leur forme exacte « <span>Libellé</span> »
 * afin de ne pas les confondre avec la légende de la page.
 */

function reservationsPageAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function reservationsPageClient(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'client'], $attributes));
}

function reservationsPageMake(User $client, array $attributes = []): Reservation
{
    return Reservation::create(array_merge([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ], $attributes));
}

it('shows the action legend and the three-dot menu with french labels', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client);

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk();

    // Légende informative.
    $response->assertSee('Légende')
        ->assertSee('Marquer présent')
        ->assertSee('Marquer absent')
        // La légende affiche l'apostrophe brute : on compare sans échappement.
        ->assertSee("Excuser l'absence", false)
        ->assertSee('Masquer');

    // Menu à trois points, jamais rogné et libellé en français.
    $response->assertSee('role="menu"', false)
        ->assertSee('aria-label="Actions pour la réservation de ', false)
        ->assertSee('<span>Confirmer</span>', false)
        ->assertSee('<span>Rejeter</span>', false);
});

it('offers only confirm and reject for a pending reservation', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    $response->assertSee('<span>Confirmer</span>', false)
        ->assertSee('<span>Rejeter</span>', false)
        ->assertDontSee('<span>Annuler</span>', false)
        ->assertDontSee('<span>Marquer présent</span>', false)
        ->assertDontSee('<span>Masquer</span>', false);
});

it('offers only cancel while the confirmed time slot has not ended', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, ['status' => Reservation::STATUS_CONFIRMED]);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    $response->assertSee('<span>Annuler</span>', false)
        ->assertDontSee('<span>Marquer présent</span>', false)
        ->assertDontSee('<span>Marquer absent</span>', false)
        ->assertDontSee('<span>Masquer</span>', false);
});

it('offers attendance once the confirmed time slot has ended', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    $response->assertSee('<span>Marquer présent</span>', false)
        ->assertSee('<span>Marquer absent</span>', false)
        ->assertDontSee('<span>Annuler</span>', false)
        ->assertDontSee('<span>Masquer</span>', false);
});

it('offers only hiding after a presence has been recorded', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'attendance' => Reservation::ATTENDANCE_PRESENT,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    $response->assertSee('<span>Masquer</span>', false)
        ->assertDontSee('<span>Marquer présent</span>', false)
        ->assertDontSee('<span>Marquer absent</span>', false)
        ->assertDontSee('<span>Annuler</span>', false);
});

it('offers excusing only when an absence has been recorded', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'attendance' => Reservation::ATTENDANCE_ABSENT,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    // Le libellé du menu passe par {{ }} : l'apostrophe y est échappée en HTML.
    $response->assertSee('<span>Excuser l&#039;absence</span>', false)
        ->assertSee('<span>Masquer</span>', false)
        ->assertDontSee('<span>Marquer présent</span>', false)
        ->assertDontSee('<span>Marquer absent</span>', false);
});

it('offers restoring a hidden reservation', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'attendance' => Reservation::ATTENDANCE_PRESENT,
        'hidden_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'hidden']))
        ->assertOk();

    $response->assertSee('<span>Réafficher</span>', false)
        ->assertDontSee('<span>Masquer</span>', false);
});

it('keeps search and filters working with the new markup', function () {
    $admin = reservationsPageAdmin();
    $kept = reservationsPageClient(['name' => 'Client Gardé']);
    $other = reservationsPageClient(['name' => 'Client Écarté']);
    reservationsPageMake($kept);
    reservationsPageMake($other);

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['search' => 'Gardé']))
        ->assertOk()
        ->assertSee('Client Gardé')
        ->assertDontSee('Client Écarté');
});

it('offers no action for a rejected reservation before its time slot ends', function () {
    $admin = reservationsPageAdmin();
    $client = reservationsPageClient();
    reservationsPageMake($client, ['status' => Reservation::STATUS_REJECTED]);

    $response = $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();

    // Aucun menu d'actions : rien n'est proposé avant la fin du créneau.
    $response->assertDontSee('role="menu"', false)
        ->assertDontSee('<span>Masquer</span>', false)
        ->assertSee('<span>Refusée</span>', false);
});

it('refuses the reservation pages and actions to a non-admin', function () {
    $client = reservationsPageClient();
    $reservation = reservationsPageMake($client);

    $this->actingAs($client)->get(route('admin.reservations.index'))->assertForbidden();

    // Requêtes fabriquées à la main : le serveur refuse aussi.
    $this->actingAs($client)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertForbidden();

    $this->actingAs($client)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertForbidden();

    expect($reservation->fresh()->hidden_at)->toBeNull()
        ->and($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN);
});