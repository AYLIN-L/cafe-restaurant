<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;
use App\Services\TableAssignmentService;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Carbon;

/**
 * Tests de la disponibilité unifiée : horaires, pas de créneau, durée +
 * temps de préparation, vérification AJAX et re-validation serveur.
 */
it('returns availability as JSON with slots and end time', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);
    $date = now()->addDay()->toDateString();

    $response = $this->actingAs($client)->getJson(route('reservations.availability', [
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
    ]));

    $response->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('end_time', '14:15')
        ->assertJsonPath('duration_minutes', 120);

    expect($response->json('slots'))->toHaveCount(count(app(TableAssignmentService::class)->slotTimes()))
        ->and(collect($response->json('slots'))->firstWhere('time', '12:00')['available'])->toBeTrue();
});

it('rejects an availability request outside opening hours', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->getJson(route('reservations.availability', [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '07:00',
            'guests_count' => 2,
        ]))
        ->assertStatus(422)
        ->assertJsonPath('available', false);
});

it('rejects an availability request that is not on the 30-minute step', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->getJson(route('reservations.availability', [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:10',
            'guests_count' => 2,
        ]))
        ->assertStatus(422)
        ->assertJsonPath('available', false);
});

it('reports a slot as unavailable when a confirmed reservation blocks it', function () {
    $this->seed(TableSeeder::class);
    // Toutes les tables sauf la n°1 sont désactivées : un seul conflit possible.
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    // Un second client sert à sonder la disponibilité : la règle « une seule
    // réservation par jour et par personne » s'applique au demandeur, elle ne
    // doit pas masquer le blocage de table que l'on cherche ici à vérifier.
    $probe = User::factory()->create(['role' => 'client']);
    $date = now()->addDay()->toDateString();

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('success');

    // 12:00 occupe la table jusqu'à 14:15 (2 h + 15 min).
    $this->actingAs($probe)
        ->getJson(route('reservations.availability', [
            'reservation_date' => $date,
            'reservation_time' => '14:00',
            'guests_count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('available', false);

    // 14:30 est le premier créneau de la grille libre après le temps de préparation.
    $this->actingAs($probe)
        ->getJson(route('reservations.availability', [
            'reservation_date' => $date,
            'reservation_time' => '14:30',
            'guests_count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('available', true);
});

it('never exposes table identifiers in the availability payload', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $response = $this->actingAs($client)->getJson(route('reservations.availability', [
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
    ]));

    expect(json_encode($response->json()))->not->toContain('table_id');
});

// ---------------------------------------------------------------------------
// Re-validation serveur à l'envoi
// ---------------------------------------------------------------------------

it('rejects a booking outside opening hours', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '07:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_time');

    expect(Reservation::count())->toBe(0);
});

it('rejects a booking that ends after closing time', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    // 21:00 + 2 h = 23:00 > fermeture à 22:00.
    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '21:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_time');
});

it('rejects a booking that is not on the slot step', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:15',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_time');
});

it('rejects a booking for a party larger than any table', function () {
    $this->seed(TableSeeder::class);
    // On ne garde que des tables de 2 places.
    Table::where('capacity', '>', 2)->update(['is_active' => false]);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 6,
        ])
        ->assertSessionHasErrors('guests_count');

    expect(Reservation::count())->toBe(0);
});

it('stores a booking without assigning a table (pending)', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertRedirect(route('reservations.index'));

    $reservation = Reservation::first();

    expect($reservation->status)->toBe('pending')
        ->and($reservation->table_id)->toBeNull();
});

it('re-validates availability at submit time when the slot was just taken', function () {
    $this->seed(TableSeeder::class);
    // Une seule table exploitable.
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $date = now()->addDay()->toDateString();

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => $date,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    // L'admin confirme : la seule table est prise jusqu'à 14:15.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', Reservation::first()), ['status' => 'confirmed']);

    $other = User::factory()->create(['role' => 'client']);

    $this->actingAs($other)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '12:30',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_time');

    expect(Reservation::where('user_id', $other->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Annulation par le client
// ---------------------------------------------------------------------------

it('releases the table when the client cancels', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);

    expect($reservation->fresh()->table_id)->not->toBeNull();

    $this->actingAs($client)
        ->patch(route('reservations.cancel', $reservation))
        ->assertSessionHas('success');

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->table_id)->toBeNull();
});

it('refuses to cancel a reservation that belongs to someone else', function () {
    $this->seed(TableSeeder::class);
    $owner = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);

    $reservation = Reservation::create([
        'user_id' => $owner->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    $this->actingAs($intruder)
        ->patch(route('reservations.cancel', $reservation))
        ->assertForbidden();

    expect($reservation->fresh()->status)->toBe('pending');
});

// ---------------------------------------------------------------------------
// Transitions strictes
// ---------------------------------------------------------------------------

it('refuses to confirm an already cancelled reservation', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CANCELLED,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->table_id)->toBeNull();
});

it('refuses to reject a confirmed reservation', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'rejected'])
        ->assertSessionHas('error');

    expect($reservation->fresh()->status)->toBe('confirmed');
});

// ---------------------------------------------------------------------------
// Statut dérivé « terminée »
// ---------------------------------------------------------------------------

it('derives the completed label from the elapsed time', function () {
    Carbon::setTestNow(now()->setTime(16, 0));

    $client = User::factory()->create(['role' => 'client']);
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    expect($reservation->displayStatus())->toBe('completed');

    Carbon::setTestNow();
});

it('keeps the confirmed label while the reservation is still running', function () {
    Carbon::setTestNow(now()->setTime(13, 0));

    $client = User::factory()->create(['role' => 'client']);
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    expect($reservation->displayStatus())->toBe('confirmed');

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// Clients sans réservation : heure d'arrivée facultative
// ---------------------------------------------------------------------------

it('accepts a walk-in with an optional past arrival time', function () {
    $this->seed(TableSeeder::class);
    Carbon::setTestNow(now()->setTime(13, 0));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), [
            'guests_count' => 2,
            'seated_time' => '12:00',
        ])
        ->assertSessionHas('success');

    expect(WalkIn::first()->seated_at->format('H:i'))->toBe('12:00');

    Carbon::setTestNow();
});

it('rejects a walk-in arrival time in the future', function () {
    $this->seed(TableSeeder::class);
    Carbon::setTestNow(now()->setTime(13, 0));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), [
            'guests_count' => 2,
            'seated_time' => '14:00',
        ])
        ->assertSessionHasErrors('seated_time');

    expect(WalkIn::count())->toBe(0);

    Carbon::setTestNow();
});

it('marks a walk-in as overdue without releasing the table', function () {
    $this->seed(TableSeeder::class);
    Carbon::setTestNow(now()->setTime(10, 0));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    $walkIn = WalkIn::first();
    expect($walkIn->isOverdue($walkIn->seated_at))->toBeFalse();

    // L'alerte apparaît, mais la table n'est jamais libérée automatiquement.
    Carbon::setTestNow($walkIn->seated_at->copy()->addHours(3));

    expect($walkIn->isOverdue(now()))->toBeTrue()
        ->and($walkIn->fresh()->released_at)->toBeNull()
        ->and($walkIn->fresh()->isActive())->toBeTrue();

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// Page de réservation
// ---------------------------------------------------------------------------

it('renders the reservation form with slots and rules', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)
        ->get(route('reservations.create'))
        ->assertOk()
        ->assertSee('Réserver une table')
        ->assertSee('20:00');
});

it('displays the assigned table and duration for the client', function () {
    $this->seed(TableSeeder::class);
    $client = User::factory()->create(['role' => 'client']);
    $table = Table::where('number', 1)->first();

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => $table->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $this->actingAs($client)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('n°1')
        ->assertSee('12:00')
        ->assertSee('14:00');
});
