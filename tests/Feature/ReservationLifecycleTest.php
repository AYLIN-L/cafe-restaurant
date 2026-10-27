<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Cycle de vie : expiration des demandes en attente, masquage,
 * blocage automatique.
 *
 * Une demande expirée n'a jamais été confirmée : elle ne peut donc jamais
 * être marquée absente et ne pénalise jamais le client. Elle est
 * conservée (aucune suppression automatique) et libère la journée.
 */
function lifecycleClient(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'client'], $attributes));
}

function lifecycleAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function lifecyclePending(User $user, array $attributes = []): Reservation
{
    return Reservation::create(array_merge([
        'user_id' => $user->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => now()->addDay()->toDateString(),
    ], $attributes));
}

it('expires pending requests older than the configured delay', function () {
    $client = lifecycleClient();
    $hours = (int) config('reservations.pending_expiration_hours', 24);

    $old = lifecyclePending($client);
    $old->forceFill(['created_at' => now()->subHours($hours + 1)])->saveQuietly();

    // La demande récente occupe une autre journée : elle n'est pas expirée.
    $recent = lifecyclePending($client, [
        'reservation_date' => now()->addDays(2)->toDateString(),
        'active_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->artisan('reservations:expire-pending')->assertSuccessful();

    $old->refresh();
    $recent->refresh();

    expect($old->status)->toBe(Reservation::STATUS_CANCELLED)
        ->and($old->expired_at)->not->toBeNull()
        ->and($old->active_date)->toBeNull()
        ->and($old->displayStatus())->toBe('expired')
        // La demande est conservée : aucune suppression automatique.
        ->and(Reservation::find($old->id))->not->toBeNull()
        // Une demande récente n'est pas expirée.
        ->and($recent->status)->toBe(Reservation::STATUS_PENDING)
        ->and($recent->expired_at)->toBeNull()
        ->and($recent->displayStatus())->toBe('pending');
});

it('never counts an expired request as an absence', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    $expired = lifecyclePending($client);
    $expired->forceFill([
        'created_at' => now()->subHours((int) config('reservations.pending_expiration_hours', 24) + 1),
    ])->saveQuietly();

    $this->artisan('reservations:expire-pending')->assertSuccessful();

    // La présence ne peut pas être enregistrée sur une demande expirée.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $expired), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('error');

    expect($expired->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN)
        ->and($client->fresh()->absencesCount())->toBe(0)
        ->and($client->fresh()->isBookingBlocked())->toBeFalse();
});

it('frees the day of an expired request', function () {
    $client = lifecycleClient();

    $expired = lifecyclePending($client);
    $expired->forceFill([
        'created_at' => now()->subHours((int) config('reservations.pending_expiration_hours', 24) + 1),
    ])->saveQuietly();

    $this->artisan('reservations:expire-pending')->assertSuccessful();

    expect($expired->fresh()->active_date)->toBeNull();
});

it('hides a reservation without deleting it and restores it', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    $reservation = lifecyclePending($client, [
        'reservation_date' => now()->subDay()->toDateString(),
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertSessionHas('success');

    $reservation->refresh();

    expect($reservation->hidden_at)->not->toBeNull()
        ->and($reservation->hidden_by)->toBe($admin->id)
        // La réservation n'est jamais supprimée.
        ->and(Reservation::find($reservation->id))->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.reservations.restore', $reservation))
        ->assertSessionHas('success');

    expect($reservation->fresh()->hidden_at)->toBeNull();
});

it('does not hide an active reservation', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    $reservation = lifecyclePending($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertSessionHas('error');

    expect($reservation->fresh()->hidden_at)->toBeNull();
});

it('hides the reservations of the hidden filter only', function () {
    $admin = lifecycleAdmin();
    $visibleOwner = lifecycleClient(['name' => 'Client Visible']);
    $hiddenOwner = lifecycleClient(['name' => 'Client Masqué']);

    $visible = lifecyclePending($visibleOwner, [
        'reservation_date' => now()->subDay()->toDateString(),
        'active_date' => null,
    ]);

    $hidden = lifecyclePending($hiddenOwner, [
        'reservation_date' => now()->subDays(2)->toDateString(),
        'active_date' => null,
    ]);

    $this->actingAs($admin)->patch(route('admin.reservations.hide', $hidden));

    // La liste par défaut n'affiche que les réservations visibles.
    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertSee('Client Visible')
        ->assertDontSee('Client Masqué');

    // Le filtre « masquées » n'affiche que la réservation masquée.
    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'hidden']))
        ->assertOk()
        ->assertSee('Client Masqué')
        ->assertDontSee('Client Visible');
});

it('blocks clients at the absence threshold with the scheduled command', function () {
    $client = lifecycleClient();
    $threshold = (int) config('reservations.absence_threshold', 5);

    for ($i = 0; $i < $threshold; $i++) {
        Reservation::create([
            'user_id' => $client->id,
            'reservation_date' => now()->subDays($i + 1)->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
            'status' => Reservation::STATUS_CONFIRMED,
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ]);
    }

    expect($client->fresh()->isBookingBlocked())->toBeFalse();

    $this->artisan('reservations:refresh-blocks')->assertSuccessful();

    expect($client->fresh()->isBookingBlocked())->toBeTrue();
});

it('never releases a blocked client with the scheduled command', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    // Blocage manuel : la commande ne doit jamais l'annuler.
    $client->blockBooking();

    $this->artisan('reservations:refresh-blocks')->assertSuccessful();

    expect($client->fresh()->isBookingBlocked())->toBeTrue();

    // Seule une décision humaine débloque le client.
    $this->actingAs($admin)
        ->patch(route('admin.users.unblock', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Workflow administrateur : annulation et masquage après le créneau
// ---------------------------------------------------------------------------

it('cancels a confirmed reservation while its time slot has not ended', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    $reservation = lifecyclePending($client, ['status' => Reservation::STATUS_CONFIRMED]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CANCELLED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->status)->toBe(Reservation::STATUS_CANCELLED)
        ->and($reservation->fresh()->table_id)->toBeNull()
        ->and($reservation->fresh()->active_date)->toBeNull();
});

it('refuses to cancel a confirmed reservation once its time slot has ended', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient();

    $reservation = lifecyclePending($client, [
        'reservation_date' => now()->subDay()->toDateString(),
        'active_date' => now()->subDay()->toDateString(),
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CANCELLED])
        ->assertSessionHas('error');

    expect($reservation->fresh()->status)->toBe(Reservation::STATUS_CONFIRMED);
});

it('keeps a hidden reservation out of the active list after a reload', function () {
    $admin = lifecycleAdmin();
    $client = lifecycleClient(['name' => 'Client Masqué Maintenant']);

    $reservation = lifecyclePending($client, [
        'reservation_date' => now()->subDay()->toDateString(),
        'active_date' => null,
        'status' => Reservation::STATUS_CONFIRMED,
        'attendance' => Reservation::ATTENDANCE_PRESENT,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertSessionHas('success');

    // Deux rechargements successifs : la réservation ne réapparaît jamais.
    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertDontSee('Client Masqué Maintenant');

    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertDontSee('Client Masqué Maintenant')
        ->assertSee('Aucune réservation pour le moment', false);

    // La donnée existe toujours en base.
    expect(Reservation::find($reservation->id))->not->toBeNull()
        ->and($reservation->fresh()->hidden_at)->not->toBeNull();
});