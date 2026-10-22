<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function clientUser(): User
{
    return User::factory()->create(['role' => 'client']);
}

function seedTables(): void
{
    test()->seed(TableSeeder::class);
}

function makeReservation(User $user, array $attributes = []): Reservation
{
    return Reservation::create(array_merge([
        'user_id' => $user->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
    ], $attributes));
}

// ---------------------------------------------------------------------------
// Attribution automatique
// ---------------------------------------------------------------------------

it('attributes the smallest suitable table when confirming a reservation', function () {
    seedTables();
    $admin = adminUser();
    $reservation = makeReservation(clientUser(), ['guests_count' => 2]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('success');

    $reservation->refresh();

    expect($reservation->status)->toBe('confirmed')
        ->and($reservation->table_id)->toBe(Table::where('number', 1)->value('id'));
});

it('picks the smallest table that fits the party size', function () {
    seedTables();
    $admin = adminUser();
    $reservation = makeReservation(clientUser(), ['guests_count' => 3]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);

    expect($reservation->fresh()->table->number)->toBe(5); // 4 places = plus petite >= 3
});

it('gives different tables to overlapping reservations', function () {
    seedTables();
    $admin = adminUser();
    $first = makeReservation(clientUser(), ['guests_count' => 2]);
    $second = makeReservation(clientUser(), ['guests_count' => 2]);

    $this->actingAs($admin)->patch(route('admin.reservations.status', $first), ['status' => 'confirmed']);
    $this->actingAs($admin)->patch(route('admin.reservations.status', $second), ['status' => 'confirmed']);

    expect($first->fresh()->table->number)->toBe(1)
        ->and($second->fresh()->table->number)->toBe(2);
});

it('reuses a table when the time slots do not overlap', function () {
    seedTables();
    $admin = adminUser();
    $early = makeReservation(clientUser(), ['guests_count' => 2, 'reservation_time' => '12:00']);
    // 12:00 bloque la table jusqu'à 14:15 (2 h + 15 min de préparation).
    // 14:00 serait refusé ; 14:15 est le premier créneau libre.
    $later = makeReservation(clientUser(), ['guests_count' => 2, 'reservation_time' => '14:15']);

    $this->actingAs($admin)->patch(route('admin.reservations.status', $early), ['status' => 'confirmed']);
    $this->actingAs($admin)->patch(route('admin.reservations.status', $later), ['status' => 'confirmed']);

    expect($early->fresh()->table->number)->toBe(1)
        ->and($later->fresh()->table->number)->toBe(1); // même table, créneaux non chevauchants
});

it('refuses a reservation that starts before the previous buffer ends', function () {
    seedTables();
    // Une seule table en service : impossible d'esquiver le conflit.
    Table::where('number', '!=', 1)->update(['is_active' => false]);
    $admin = adminUser();
    $early = makeReservation(clientUser(), ['guests_count' => 2, 'reservation_time' => '12:00']);
    // 14:00 est encore dans la période de préparation (jusqu'à 14:15).
    $tooSoon = makeReservation(clientUser(), ['guests_count' => 2, 'reservation_time' => '14:00']);

    $this->actingAs($admin)->patch(route('admin.reservations.status', $early), ['status' => 'confirmed']);
    $this->actingAs($admin)->patch(route('admin.reservations.status', $tooSoon), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    expect($early->fresh()->table->number)->toBe(1)
        ->and($tooSoon->fresh()->status)->toBe('pending')
        ->and($tooSoon->fresh()->table_id)->toBeNull();
});

it('skips inactive tables', function () {
    seedTables();
    Table::where('number', 1)->update(['is_active' => false]);
    $admin = adminUser();
    $reservation = makeReservation(clientUser(), ['guests_count' => 2]);

    $this->actingAs($admin)->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);

    expect($reservation->fresh()->table->number)->toBe(2);
});

it('refuses confirmation when no table is available', function () {
    seedTables();
    // Aucune table ne peut accueillir 6 personnes.
    Table::whereIn('number', [9, 10])->update(['is_active' => false]);
    $admin = adminUser();
    $reservation = makeReservation(clientUser(), ['guests_count' => 6]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    $reservation->refresh();

    expect($reservation->status)->toBe('pending')
        ->and($reservation->table_id)->toBeNull();
});

it('releases the assigned table when a confirmed reservation is cancelled', function () {
    seedTables();
    $admin = adminUser();
    $reservation = makeReservation(clientUser(), ['guests_count' => 2]);

    $this->actingAs($admin)->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);
    expect($reservation->fresh()->table_id)->not->toBeNull();

    $this->actingAs($admin)->patch(route('admin.reservations.status', $reservation), ['status' => 'cancelled']);

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->table_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Clients sans réservation (walk-in)
// ---------------------------------------------------------------------------

it('seats a walk-in on the smallest free table', function () {
    seedTables();
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), ['guests_count' => 2])
        ->assertSessionHas('success');

    $walkIn = WalkIn::first();

    expect($walkIn)->not->toBeNull()
        ->and($walkIn->table->number)->toBe(1)
        ->and($walkIn->isActive())->toBeTrue();
});

it('does not seat two walk-ins on the same table', function () {
    seedTables();
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);
    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    expect(WalkIn::pluck('table_id')->unique())->toHaveCount(2);
});

it('fails to seat a walk-in when no table is free', function () {
    seedTables();
    Table::query()->update(['is_active' => false]);
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), ['guests_count' => 2])
        ->assertSessionHas('error');

    expect(WalkIn::count())->toBe(0);
});

it('releases a walk-in table manually', function () {
    seedTables();
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);
    $walkIn = WalkIn::first();

    $this->actingAs($admin)
        ->patch(route('admin.walkins.release', $walkIn))
        ->assertSessionHas('success');

    expect($walkIn->fresh()->isActive())->toBeFalse();
});

it('avoids tables with an upcoming booking today when seating a walk-in', function () {
    seedTables();
    Carbon::setTestNow(now()->setTime(11, 0));

    $admin = adminUser();
    // Réservation confirmée aujourd'hui à 12:00 -> occupe la table n°1.
    $reservation = makeReservation(clientUser(), [
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
    ]);
    $this->actingAs($admin)->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);
    expect($reservation->fresh()->table->number)->toBe(1);

    // Le client sans réservation ne doit pas prendre la table n°1 (réservée à 12:00).
    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 2]);

    expect(WalkIn::first()->table->number)->toBe(2);

    Carbon::setTestNow();
});

it('refuses to confirm a reservation on a table occupied by a walk-in', function () {
    seedTables();
    Carbon::setTestNow(now()->setTime(12, 0));

    // Une seule table peut accueillir 6 personnes -> elle est occupée par un walk-in.
    Table::where('number', 9)->update(['is_active' => false]);
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 6]);
    expect(WalkIn::first()->table->number)->toBe(10);

    // Le client sans réservation n'est jamais libéré automatiquement : la
    // table reste occupée même une fois la durée habituelle dépassée.
    $reservation = makeReservation(clientUser(), [
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:30',
        'guests_count' => 6,
    ]);
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    expect($reservation->fresh()->status)->toBe('pending');

    Carbon::setTestNow();
});

it('frees the table once the walk-in is released', function () {
    seedTables();
    Carbon::setTestNow(now()->setTime(12, 0));

    Table::where('number', 9)->update(['is_active' => false]);
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.walkins.store'), ['guests_count' => 6]);
    $walkIn = WalkIn::first();
    expect($walkIn->table->number)->toBe(10);

    $this->actingAs($admin)->patch(route('admin.walkins.release', $walkIn));

    $reservation = makeReservation(clientUser(), [
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '12:30',
        'guests_count' => 6,
    ]);
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('success');

    expect($reservation->fresh()->table->number)->toBe(10);

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// Fenêtre de réservation client (7 jours)
// ---------------------------------------------------------------------------

it('accepts a booking within the 7-day window', function () {
    seedTables();
    $client = clientUser();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDays(7)->toDateString(),
            'reservation_time' => '19:00',
            'guests_count' => 2,
        ])
        ->assertRedirect(route('reservations.index'));

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('rejects a booking beyond the 7-day window', function () {
    $client = clientUser();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDays(8)->toDateString(),
            'reservation_time' => '19:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');

    expect(Reservation::count())->toBe(0);
});

it('rejects a booking in the past', function () {
    $client = clientUser();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->subDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');
});

// ---------------------------------------------------------------------------
// Conservation de l'historique (aucune purge automatique)
// ---------------------------------------------------------------------------

it('never deletes reservations automatically', function () {
    $user = clientUser();

    $old = makeReservation($user, ['reservation_date' => now()->subDays(100)->toDateString()]);
    $recent = makeReservation($user, ['reservation_date' => now()->subDays(10)->toDateString()]);
    $future = makeReservation($user, ['reservation_date' => now()->addDays(3)->toDateString()]);

    // Aucune commande de purge automatique n'est enregistrée : l'historique
    // des réservations est conservé indéfiniment.
    expect(array_keys(Artisan::all()))
        ->not->toContain('reservations:purge')
        ->and(Reservation::find($old->id))->not->toBeNull()
        ->and(Reservation::find($recent->id))->not->toBeNull()
        ->and(Reservation::find($future->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Interface d'administration des tables
// ---------------------------------------------------------------------------

it('renders the tables management page for admins', function () {
    seedTables();
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.tables.index'))
        ->assertOk()
        ->assertSee('Gestion des tables')
        ->assertSee('Table n°1');
});

it('forbids non-admins from the tables management page', function () {
    $this->actingAs(clientUser())
        ->get(route('admin.tables.index'))
        ->assertForbidden();
});

it('toggles a table active state', function () {
    seedTables();
    $admin = adminUser();
    $table = Table::where('number', 1)->first();

    $this->actingAs($admin)
        ->patch(route('admin.tables.toggle', $table))
        ->assertSessionHas('success');

    expect($table->fresh()->is_active)->toBeFalse();
});

it('prevents deleting a table that has reservations', function () {
    seedTables();
    $admin = adminUser();
    $table = Table::where('number', 1)->first();
    makeReservation(clientUser(), ['table_id' => $table->id, 'status' => 'confirmed']);

    $this->actingAs($admin)
        ->delete(route('admin.tables.destroy', $table))
        ->assertSessionHas('error');

    expect(Table::find($table->id))->not->toBeNull();
});

it('deletes a table without reservations', function () {
    seedTables();
    $admin = adminUser();
    $table = Table::where('number', 10)->first();

    $this->actingAs($admin)
        ->delete(route('admin.tables.destroy', $table))
        ->assertSessionHas('success');

    expect(Table::find($table->id))->toBeNull();
});

it('creates a new table', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.tables.store'), ['number' => 11, 'capacity' => 4, 'is_active' => 1])
        ->assertRedirect(route('admin.tables.index'));

    expect(Table::where('number', 11)->where('capacity', 4)->exists())->toBeTrue();
});

it('rejects a duplicate table number', function () {
    seedTables();
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.tables.store'), ['number' => 1, 'capacity' => 2])
        ->assertSessionHasErrors('number');
});
