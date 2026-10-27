<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Tests des compteurs du tableau de bord.
 *
 * Les statistiques de la salle (tables, places, occupation) ne figurent plus
 * ici : elles appartiennent à la gestion des tables (voir
 * TableManagementStatsTest).
 */
afterEach(function () {
    Carbon::setTestNow();
});

it('renders the dashboard for admins', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee("Réservations aujourd'hui", false)
        ->assertSee('Clients inscrits')
        ->assertSee('Plats au menu')
        ->assertSee('Catégories')
        ->assertSee('Avis à modérer');
});

it('does not show the table statistics on the dashboard', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    // Les statistiques de la salle sont centralisées dans la gestion des
    // tables : le tableau de bord ne les affiche plus.
    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Tables occupées')
        ->assertDontSee('Places assises libres')
        ->assertDontSee('Clients sans réservation');
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

it('counts reservations on their reservation date and not on their creation date', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    // Créée aujourd'hui mais planifiée pour hier : elle ne compte pas
    // dans les réservations du jour.
    $past = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);
    $past->forceFill(['created_at' => now()])->save();

    // Créée il y a trois jours mais planifiée pour aujourd'hui : elle compte.
    $today = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);
    $today->forceFill(['created_at' => now()->subDays(3)])->save();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    expect($response->viewData('todayCount'))->toBe(1);
});

it('counts week reservations excluding cancelled and rejected ones', function () {
    // Mercredi : les trois jours suivants tombent dans la même semaine.
    Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00'));

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

it('counts the week from monday to sunday', function () {
    // Mercredi 21 octobre 2026 : la semaine court du 19 au 25 octobre.
    Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00'));

    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    $create = fn (string $day) => Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => $day,
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    // Lundi et dimanche de la semaine courante : comptés.
    $create('2026-10-19');
    $create('2026-10-25');

    // Semaine précédente et semaine suivante : hors semaine courante.
    $create('2026-10-18');
    $create('2026-10-26');

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    expect($response->viewData('weekCount'))->toBe(2)
        ->and($response->viewData('todayCount'))->toBe(0);
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
