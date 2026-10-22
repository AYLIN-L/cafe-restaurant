<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\TableSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Conflits : deux demandes simultanées pour la même journée ou pour la même
 * table ne peuvent pas aboutir toutes les deux.
 *
 * La protection finale est l'index unique (user_id, active_date) : même si
 * deux requêtes passent la vérification applicative, la base refuse la
 * seconde réservation.
 */
function concurrencyClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

function concurrencyAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('refuses a duplicate active reservation for the same day at the database level', function () {
    $this->seed(TableSeeder::class);
    $client = concurrencyClient();
    $date = now()->addDay()->toDateString();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    // Insertion directe (contournement de l'interface) : l'index unique
    // refuse la seconde réservation active de la même journée.
    expect(fn () => Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '18:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('allows several historical reservations for the same day when the day is released', function () {
    $this->seed(TableSeeder::class);
    $client = concurrencyClient();
    $date = now()->addDay()->toDateString();

    // Première réservation, puis libération de la journée.
    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]);

    $first->update(['status' => Reservation::STATUS_CANCELLED, 'active_date' => null]);

    // Une nouvelle réservation pour la même journée est possible.
    $second = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '18:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]);

    expect($second->active_date)->not->toBeNull();
});

it('does not assign a table to a pending request', function () {
    $this->seed(TableSeeder::class);
    $client = concurrencyClient();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    $reservation = Reservation::first();

    // Une demande en attente ne retient aucune table.
    expect($reservation->status)->toBe(Reservation::STATUS_PENDING)
        ->and($reservation->table_id)->toBeNull();
});

it('assigns the table only when the request is confirmed', function () {
    $this->seed(TableSeeder::class);
    $admin = concurrencyAdmin();
    $client = concurrencyClient();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    $reservation = Reservation::first();

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    $reservation->refresh();

    expect($reservation->table_id)->toBe(Table::where('number', 1)->value('id'));
});

it('assigns the table to a single reservation when two requests compete for the same slot', function () {
    $this->seed(TableSeeder::class);

    // Une seule table disponible : la seconde confirmation doit échouer.
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $admin = concurrencyAdmin();
    $client = concurrencyClient();
    $date = now()->addDay()->toDateString();

    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]);

    $second = Reservation::create([
        'user_id' => concurrencyClient()->id,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $first), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $second), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('error');

    expect($first->fresh()->table_id)->not->toBeNull()
        ->and($second->fresh()->table_id)->toBeNull()
        ->and($second->fresh()->status)->toBe(Reservation::STATUS_PENDING);
});

it('is idempotent when the same reservation is confirmed twice', function () {
    $this->seed(TableSeeder::class);
    $admin = concurrencyAdmin();
    $client = concurrencyClient();
    $date = now()->addDay()->toDateString();

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $date,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    $tableId = $reservation->fresh()->table_id;

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->table_id)->toBe($tableId)
        ->and($reservation->fresh()->status)->toBe(Reservation::STATUS_CONFIRMED);
});

it('keeps the day of the client when the reservation is confirmed', function () {
    $this->seed(TableSeeder::class);
    $admin = concurrencyAdmin();
    $client = concurrencyClient();
    $date = now()->addDay()->toDateString();

    // Réservation historique sans journée active (doublon antérieur).
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    expect($reservation->fresh()->active_date)->not->toBeNull();
});
