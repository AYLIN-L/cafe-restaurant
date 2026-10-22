<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Tests des compteurs et de l'occupation de la salle sur le tableau de bord.
 */
it('renders the dashboard for admins', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Tables occupées')
        ->assertSee('Tables libres')
        ->assertSee('Places assises libres')
        ->assertSee('Clients sans réservation');
});

it('forbids clients from the dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => 'client']))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('counts today reservations excluding cancelled and rejected ones', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $base = [
        'user_id' => $client->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
    ];

    Reservation::create($base + ['status' => Reservation::STATUS_PENDING]);
    Reservation::create($base + ['status' => Reservation::STATUS_CONFIRMED]);
    Reservation::create($base + ['status' => Reservation::STATUS_CANCELLED]);
    Reservation::create($base + ['status' => Reservation::STATUS_REJECTED]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();

    // Seules les réservations « en attente » et « confirmée » comptent.
    $todayCount = $response->viewData('todayCount');

    expect($todayCount)->toBe(2);
});

it('counts week reservations excluding cancelled and rejected ones', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $base = [
        'user_id' => $client->id,
        'guests_count' => 2,
    ];

    Reservation::create($base + [
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'status' => Reservation::STATUS_CONFIRMED,
    ]);
    Reservation::create($base + [
        'reservation_date' => now()->addDays(2)->toDateString(),
        'reservation_time' => '13:00',
        'status' => Reservation::STATUS_CANCELLED,
    ]);
    Reservation::create($base + [
        'reservation_date' => now()->addDays(3)->toDateString(),
        'reservation_time' => '14:00',
        'status' => Reservation::STATUS_REJECTED,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    expect($response->viewData('weekCount'))->toBe(1);
});

it('reports the real-time occupancy of the room', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    expect($response->viewData('occupiedTables'))->toBe(1)
        ->and($response->viewData('freeTables'))->toBe($response->viewData('activeTables')->count() - 1)
        ->and($response->viewData('activeWalkIns'))->toBe(1)
        ->and($response->viewData('totalSeats'))->toBeGreaterThan(0)
        ->and($response->viewData('seatsFree'))->toBeLessThan($response->viewData('totalSeats'));
});

it('does not count cancelled reservations as occupied tables', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $table = Table::where('number', 1)->first();

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => $table->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CANCELLED,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    expect($response->viewData('occupiedTables'))->toBe(0);
});

it('shows five table states on the tables page', function () {
    $this->seed(TableSeeder::class);
    Carbon::setTestNow(now()->setTime(12, 0));

    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    // Table n°1 : occupée par un client sans réservation.
    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    // Table n°2 : réservée pour plus tard dans la journée.
    Reservation::create([
        'user_id' => $client->id,
        'table_id' => Table::where('number', 2)->first()->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    // Table n°3 : hors service.
    Table::where('number', 3)->update(['is_active' => false]);

    $response = $this->actingAs($admin)->get(route('admin.tables.index'));

    $response->assertOk()
        ->assertSee('Occupée (sans réservation)')
        ->assertSee('Réservée')
        ->assertSee('Hors service')
        ->assertSee('Libre');

    $states = $response->viewData('tables')
        ->mapWithKeys(fn ($table) => [$table->number => $table->stateAt(now())]);

    expect($states[1])->toBe('walk_in')
        ->and($states[2])->toBe('reserved')
        ->and($states[3])->toBe('inactive')
        ->and($states[4])->toBe('free');

    Carbon::setTestNow();
});

it('allows deleting a table that only has cancelled reservations', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $table = Table::where('number', 1)->first();

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => $table->id,
        'reservation_date' => now()->subDays(30)->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CANCELLED,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.tables.destroy', $table))
        ->assertSessionHas('success');

    expect(Table::find($table->id))->toBeNull();
});

it('blocks deleting a client that still has reservations', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $client))
        ->assertSessionHas('error');

    expect(User::find($client->id))->not->toBeNull();
});

it('deletes a client without reservations', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $client))
        ->assertSessionHas('success');

    expect(User::find($client->id))->toBeNull();
});

it('never deletes walk-ins automatically', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $table1 = Table::where('number', 1)->first();
    $table2 = Table::where('number', 2)->first();

    // Session libérée il y a plus de 90 jours : conservée.
    $old = WalkIn::create([
        'table_id' => $table1->id,
        'guests_count' => 2,
        'seated_at' => now()->subDays(120),
        'released_at' => now()->subDays(100),
    ]);

    // Session jamais libérée : conservée également.
    $active = WalkIn::create([
        'table_id' => $table2->id,
        'guests_count' => 2,
        'seated_at' => now()->subDays(120),
    ]);

    // Aucune purge automatique : la commande n'existe plus.
    expect(array_keys(Artisan::all()))
        ->not->toContain('reservations:purge')
        ->and(WalkIn::find($old->id))->not->toBeNull()
        ->and(WalkIn::find($active->id))->not->toBeNull();
});
