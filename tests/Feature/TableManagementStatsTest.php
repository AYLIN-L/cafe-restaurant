<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Carbon;

/**
 * Tests des statistiques de la salle.
 *
 * Elles sont calculées une seule fois par le contrôleur des tables
 * (TableController) et affichées sur la page « Gestion des tables » :
 * c'est désormais la source de vérité unique (le tableau de bord ne les
 * affiche plus).
 */
afterEach(function () {
    Carbon::setTestNow();
});

it('reports the real-time occupancy of the room', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    $response = $this->actingAs($admin)->get(route('admin.tables.index'));

    $stats = $response->viewData('stats');

    expect($stats['occupied'])->toBe(1)
        ->and($stats['walkIns'])->toBe(1)
        ->and($stats['tablesActive'])->toBe($stats['tablesTotal'])
        ->and($stats['free'])->toBe($stats['tablesActive'] - 1)
        ->and($stats['seatsFree'])->toBeLessThan($stats['seatsActive']);
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

    $response = $this->actingAs($admin)->get(route('admin.tables.index'));

    expect($response->viewData('stats')['occupied'])->toBe(0);
});

it('keeps the seats of the tables in service as the headline figure', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    // La table n°3 (2 places) est mise hors service.
    Table::where('number', 3)->update(['is_active' => false]);

    $response = $this->actingAs($admin)->get(route('admin.tables.index'));

    $stats = $response->viewData('stats');

    // Chiffre principal : les places des tables en service.
    expect($stats['seatsActive'])->toBe(36 - 2)
        ->and($stats['seatsTotal'])->toBe(36)
        ->and($stats['tablesActive'])->toBe(9)
        ->and($stats['tablesTotal'])->toBe(10)
        ->and($stats['tablesInactive'])->toBe(1);

    // Le total de la salle est affiché en second, sans ambiguïté.
    $response->assertOk()
        ->assertSee('Places assises')
        ->assertSee('36 au total (tables hors service incluses)');
});

it('shows every table statistic on the tables page', function () {
    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.tables.index'));

    $response->assertOk()
        ->assertSee('Tables en service')
        ->assertSee('Places assises')
        ->assertSee('Occupées maintenant')
        ->assertSee('Réservées')
        ->assertSee('Libres')
        ->assertSee('Clients sans réservation');
});

it('counts the tables reserved later in the day', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00'));

    $this->seed(TableSeeder::class);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    Reservation::create([
        'user_id' => $client->id,
        'table_id' => Table::where('number', 1)->first()->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $stats = $this->actingAs($admin)->get(route('admin.tables.index'))->viewData('stats');

    expect($stats['reserved'])->toBe(1)
        ->and($stats['occupied'])->toBe(0);
});
