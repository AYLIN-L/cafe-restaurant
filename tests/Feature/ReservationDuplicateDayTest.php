<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\TableSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Règle « une réservation par client et par jour ».
 *
 * La journée du client est matérialisée par la colonne `active_date` : une
 * seule réservation peut la détenir. La confirmation d'une seconde
 * réservation pour la même journée est donc refusée.
 *
 * Protection en deux temps :
 *  1. le contrôle applicatif (dans la transaction de confirmation) refuse
 *     la confirmation si une autre réservation du client détient déjà la
 *     journée ;
 *  2. l'index unique (user_id, active_date) tranche le cas des
 *     confirmations simultanées : la seconde transaction est refusée par la
 *     base, jamais par une erreur serveur.
 */
it('refuses to confirm a second reservation for the same client on the same day', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $day = now()->addDay()->toDateString();

    // Première réservation : elle détient la journée.
    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => $day,
    ]);

    // Doublon historique : même client, même journée, sans journée active.
    $duplicate = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $duplicate), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('error')
        ->assertSessionHasNoErrors();

    // Le doublon reste intact : ni supprimé, ni modifié.
    expect($duplicate->fresh()->status)->toBe(Reservation::STATUS_PENDING)
        ->and($duplicate->fresh()->table_id)->toBeNull()
        ->and($duplicate->fresh()->active_date)->toBeNull()
        ->and(Reservation::where('user_id', $client->id)->count())->toBe(2);

    // La réservation légitime peut toujours être confirmée.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $first), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    expect($first->fresh()->status)->toBe(Reservation::STATUS_CONFIRMED);
});

it('explains the refusal to the administrator', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $day = now()->addDay()->toDateString();

    Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => $day,
    ]);

    $duplicate = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $duplicate), ['status' => Reservation::STATUS_CONFIRMED]);

    $message = session('error');

    expect($message)->toBeString()
        ->toContain('Confirmation refusée')
        ->toContain('une réservation par jour');
});

it('allows the duplicate once the day has been released', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $day = now()->addDay()->toDateString();

    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => $day,
    ]);

    $duplicate = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    // La première réservation est annulée : la journée est libérée.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $first), ['status' => Reservation::STATUS_CANCELLED])
        ->assertSessionHas('success');

    expect($first->fresh()->active_date)->toBeNull();

    // Le doublon peut alors être confirmé.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $duplicate), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    expect($duplicate->fresh()->status)->toBe(Reservation::STATUS_CONFIRMED)
        ->and($duplicate->fresh()->active_date)->not->toBeNull();
});

it('keeps only one confirmed reservation when two duplicates compete', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $day = now()->addDay()->toDateString();

    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $second = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $first), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('success');

    // La seconde confirmation est refusée : la journée est déjà prise.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $second), ['status' => Reservation::STATUS_CONFIRMED])
        ->assertSessionHas('error');

    expect($first->fresh()->status)->toBe(Reservation::STATUS_CONFIRMED)
        ->and($second->fresh()->status)->toBe(Reservation::STATUS_PENDING)
        ->and($second->fresh()->active_date)->toBeNull();
});

it('keeps the database protection when two confirmations happen at the same time', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);
    $day = now()->addDay()->toDateString();

    $first = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    $second = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ]);

    // La première confirmation prend la journée.
    expect($first->claimActiveDate())->toBeTrue();

    // Une écriture qui contourne le contrôle applicatif se heurte à l'index
    // unique (user_id, active_date) : c'est la base qui tranche, pas un
    // contrôle applicatif. C'est ce qui arrive quand deux confirmations
    // surviennent exactement en même temps.
    $racing = Reservation::whereKey($second->getKey())->firstOrFail();

    expect(fn () => $racing->forceFill(['active_date' => $day])->save())
        ->toThrow(UniqueConstraintViolationException::class);

    expect($second->fresh()->active_date)->toBeNull()
        ->and($first->fresh()->active_date)->not->toBeNull();
});
