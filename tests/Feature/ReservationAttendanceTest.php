<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Présence, absences vérifiées et blocage automatique.
 *
 * Règles :
 * - seule une réservation CONFIRMÉE peut être marquée ;
 * - seule la valeur « absent » compte pour la limite d'absences ;
 * - une demande expirée ou annulée ne compte jamais ;
 * - le compteur est toujours dérivé : il ne peut pas être compté deux fois ;
 * - le déblocage est une décision humaine et pose un « override ».
 */
function attendanceClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

function attendanceAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function attendanceReservation(User $user, array $attributes = []): Reservation
{
    return Reservation::create(array_merge([
        'user_id' => $user->id,
        'table_id' => null,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ], $attributes));
}

it('refuses to record attendance on a pending request', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client, ['status' => Reservation::STATUS_PENDING]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('refuses to record attendance on a cancelled reservation', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client, ['status' => Reservation::STATUS_CANCELLED]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN);
});

it('refuses to record attendance on an expired request', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    // Une demande expirée n'a jamais été confirmée.
    $reservation = attendanceReservation($client, [
        'status' => Reservation::STATUS_CANCELLED,
        'expired_at' => now()->subHour(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('records a verified absence on a confirmed reservation', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('success');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_ABSENT)
        ->and($reservation->fresh()->countsAsAbsence())->toBeTrue()
        ->and($client->fresh()->absencesCount())->toBe(1);
});

it('records a presence on a confirmed reservation', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertSessionHas('success');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_PRESENT)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('does not count an excused absence', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_EXCUSED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_EXCUSED)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('never counts the same absence twice', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    // Deux enregistrements successifs de la même absence.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT]);

    expect($client->fresh()->absencesCount())->toBe(1);
});

it('recomputes the absence count when the attendance changes', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT]);

    expect($client->fresh()->absencesCount())->toBe(1);

    // L'administrateur revient en arrière : l'absence ne compte plus.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_EXCUSED]);

    expect($client->fresh()->absencesCount())->toBe(0);
});

it('keeps the absence count unchanged when a reservation is hidden', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client, ['attendance' => Reservation::ATTENDANCE_ABSENT]);

    expect($client->fresh()->absencesCount())->toBe(1);

    // Le masquage ne retire JAMAIS une absence du compteur : seule une
    // absence excusée en sort.
    $reservation->update(['hidden_at' => now()]);

    expect($client->fresh()->absencesCount())->toBe(1);

    // Seule l'excuse fait sortir l'absence du compteur, même masquée.
    $reservation->update(['attendance' => Reservation::ATTENDANCE_EXCUSED]);

    expect($client->fresh()->absencesCount())->toBe(0);
});

it('blocks the client automatically when the absence threshold is reached', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();
    $threshold = (int) config('reservations.absence_threshold', 5);

    // Quatre absences déjà enregistrées.
    for ($i = 0; $i < $threshold - 1; $i++) {
        attendanceReservation($client, ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    }

    expect($client->fresh()->isBookingBlocked())->toBeFalse();

    // La cinquième absence déclenche le blocage.
    $last = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $last), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('success');

    expect($client->fresh()->absencesCount())->toBe($threshold)
        ->and($client->fresh()->isBookingBlocked())->toBeTrue();
});

it('refuses new reservations from a blocked client', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $client->blockBooking();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');
});

it('never blocks again a client explicitly unblocked by an admin', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();
    $threshold = (int) config('reservations.absence_threshold', 5);

    for ($i = 0; $i < $threshold; $i++) {
        attendanceReservation($client, ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    }

    // L'administrateur débloque : le client est explicitement exempté.
    $client->unblockBooking();

    expect($client->fresh()->isBookingBlocked())->toBeFalse()
        ->and($client->fresh()->hasBookingBlockOverride())->toBeTrue();

    // Une absence de plus ne rebloque pas le client.
    $last = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $last), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeFalse();
});

it('allows an admin to block and unblock a client', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $this->actingAs($admin)
        ->patch(route('admin.users.block', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.users.unblock', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeFalse()
        ->and($client->fresh()->hasBookingBlockOverride())->toBeTrue();
});

it('forbids a client from blocking an account', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();
    $other = attendanceClient();

    $this->actingAs($client)
        ->patch(route('admin.users.block', $other))
        ->assertForbidden();

    expect($other->fresh()->isBookingBlocked())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Créneau écoulé, transitions de présence et masquage
// ---------------------------------------------------------------------------

it('refuses to record attendance before the time slot has ended', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client, [
        'reservation_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_UNKNOWN)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('refuses to change a presence that has already been recorded', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertSessionHas('success');

    // Un clic involontaire ne doit pas transformer une présence en absence.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_PRESENT)
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('refuses to excuse an absence that was never recorded', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    // Présence enregistrée : on ne peut pas la remplacer par une excuse.
    $present = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $present), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $present), ['attendance' => Reservation::ATTENDANCE_EXCUSED])
        ->assertSessionHas('error');

    expect($present->fresh()->attendance)->toBe(Reservation::ATTENDANCE_PRESENT);
});

it('hides a confirmed reservation only once attendance is recorded', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();

    $reservation = attendanceReservation($client); // créneau écoulé

    // Présence non enregistrée : le masquage est refusé.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertSessionHas('error');

    expect($reservation->fresh()->hidden_at)->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_PRESENT])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $reservation))
        ->assertSessionHas('success');

    expect($reservation->fresh()->hidden_at)->not->toBeNull()
        // La réservation n'est jamais supprimée.
        ->and(Reservation::find($reservation->id))->not->toBeNull()
        ->and($client->fresh()->absencesCount())->toBe(0);
});

it('keeps the absence threshold consistent when absences are hidden', function () {
    $admin = attendanceAdmin();
    $client = attendanceClient();
    $threshold = (int) config('reservations.absence_threshold', 5);

    for ($i = 0; $i < $threshold - 1; $i++) {
        attendanceReservation($client, ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    }

    $last = attendanceReservation($client);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $last), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('success');

    expect($client->fresh()->absencesCount())->toBe($threshold)
        ->and($client->fresh()->isBookingBlocked())->toBeTrue();

    // Le masquage d'une des absences ne modifie ni le compteur ni le blocage.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.hide', $last))
        ->assertSessionHas('success');

    expect($client->fresh()->absencesCount())->toBe($threshold)
        ->and($client->fresh()->isBookingBlocked())->toBeTrue();
});
