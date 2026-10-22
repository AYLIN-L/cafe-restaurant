<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\TableSeeder;

/**
 * Pages client : formulaire de réservation et historique.
 *
 * Le client doit voir clairement l'état de son compte (blocage), le statut
 * réel de chaque réservation (« expirée » n'est pas stocké en base) et la
 * présence enregistrée par le restaurant.
 */
function pageClient(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'client'], $attributes));
}

it('renders the booking form for a client', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();

    $this->actingAs($client)
        ->get(route('reservations.create'))
        ->assertOk()
        ->assertSee('Réserver une table')
        ->assertDontSee('Vos nouvelles réservations sont temporairement suspendues');
});

it('warns a blocked client on the booking form', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();
    $client->blockBooking();

    $this->actingAs($client)
        ->get(route('reservations.create'))
        ->assertOk()
        ->assertSee('Vos nouvelles réservations sont temporairement suspendues');
});

it('warns a blocked client on the reservation history', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();
    $client->blockBooking();

    $this->actingAs($client)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('Vos nouvelles réservations sont temporairement suspendues');
});

it('reports a blocked account through the availability endpoint', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();
    $client->blockBooking();

    $this->actingAs($client)
        ->getJson(route('reservations.availability', [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('can_book', false)
        ->assertJsonPath('reason', 'blocked');
});

it('shows the expired status of a request that timed out', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CANCELLED,
        'expired_at' => now()->subHour(),
    ]);

    $this->actingAs($client)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('Expirée');
});

it('shows the presence recorded by the restaurant', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();

    Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'attendance' => Reservation::ATTENDANCE_ABSENT,
    ]);

    $this->actingAs($client)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('Client absent');
});

it('only offers the cancellation while the reservation can still be cancelled', function () {
    $this->seed(TableSeeder::class);
    $client = pageClient();

    // Réservation future : l'annulation est possible.
    $future = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    // Réservation passée : l'annulation n'a plus de sens.
    $past = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    expect($future->isCancellable())->toBeTrue()
        ->and($past->isCancellable())->toBeFalse();
});
