<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\TableSeeder;

/**
 * Une seule réservation par client et par journée.
 *
 * Q1 : une demande en attente ET une réservation confirmée immobilisent
 *      toutes les deux la journée du client.
 * Q2 : une annulation ou un refus libère la journée.
 */
function limitClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

function limitAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function limitReservation(User $user, array $attributes = []): Reservation
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

it('refuses a second reservation for the same day', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('counts a pending request against the daily limit', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    limitReservation($client, ['reservation_date' => $date, 'active_date' => $date]);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('counts a confirmed reservation against the daily limit', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    limitReservation($client, [
        'reservation_date' => $date,
        'active_date' => $date,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');
});

it('allows a reservation on another day', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDays(2)->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect(Reservation::where('user_id', $client->id)->count())->toBe(2);
});

it('releases the day when the client cancels', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    $reservation = limitReservation($client, ['reservation_date' => $date, 'active_date' => $date]);

    $this->actingAs($client)
        ->patch(route('reservations.cancel', $reservation))
        ->assertSessionHas('success');

    expect($reservation->fresh()->active_date)->toBeNull();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();
});

it('releases the day when the admin rejects the request', function () {
    $this->seed(TableSeeder::class);
    $admin = limitAdmin();
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    $reservation = limitReservation($client, ['reservation_date' => $date, 'active_date' => $date]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_REJECTED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->active_date)->toBeNull();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();
});

it('releases the day when the admin cancels the reservation', function () {
    $this->seed(TableSeeder::class);
    $admin = limitAdmin();
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    $reservation = limitReservation($client, [
        'reservation_date' => $date,
        'active_date' => $date,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CANCELLED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->active_date)->toBeNull();
});

it('reports the day as taken in the availability payload', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();
    $date = now()->addDay()->toDateString();

    limitReservation($client, ['reservation_date' => $date, 'active_date' => $date]);

    $this->actingAs($client)
        ->getJson(route('reservations.availability', [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'day_taken');
});

it('refuses to cancel a reservation from another client', function () {
    $this->seed(TableSeeder::class);
    $owner = limitClient();
    $other = limitClient();

    $reservation = limitReservation($owner);

    $this->actingAs($other)
        ->patch(route('reservations.cancel', $reservation))
        ->assertForbidden();

    expect($reservation->fresh()->status)->toBe(Reservation::STATUS_PENDING);
});

it('refuses to cancel a reservation that is already past', function () {
    $this->seed(TableSeeder::class);
    $client = limitClient();

    $reservation = limitReservation($client, [
        'reservation_date' => now()->subDay()->toDateString(),
        'active_date' => null,
    ]);

    $this->actingAs($client)
        ->patch(route('reservations.cancel', $reservation))
        ->assertSessionHas('error');

    expect($reservation->fresh()->status)->toBe(Reservation::STATUS_PENDING);
});
