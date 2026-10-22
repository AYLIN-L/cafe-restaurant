<?php

use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;
use App\Services\TableAssignmentService;
use Database\Seeders\TableSeeder;

/**
 * Clients sans réservation (walk-in).
 *
 * Q5 : la table reste bloquée jusqu'à la libération EXPLICITE par
 *      l'administrateur, puis pendant le temps de préparation (15 min).
 *      La session du jour n'impacte jamais le lendemain.
 */
function walkInAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function walkInClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

it('keeps the table blocked until the walk-in is explicitly released', function () {
    $this->seed(TableSeeder::class);
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $this->travelTo(now()->setTime(12, 0));

    $admin = walkInAdmin();
    $service = app(TableAssignmentService::class);

    $walkIn = $service->seatWalkIn(2, null, now(), true);

    expect($walkIn)->not->toBeNull()
        ->and($walkIn->table->number)->toBe(1)
        ->and($walkIn->isActive())->toBeTrue();

    // La table est indisponible pour un créneau qui chevauche la session.
    expect($service->isSlotAvailable(now()->toDateString(), '12:00', 2))->toBeFalse();

    // Elle reste bloquée pour les créneaux suivants de la journée.
    expect($service->isSlotAvailable(now()->toDateString(), '13:00', 2))->toBeFalse();

    // Libération explicite.
    $this->actingAs($admin)
        ->patch(route('admin.walkins.release', $walkIn))
        ->assertSessionHas('success');

    expect($walkIn->fresh()->isActive())->toBeFalse();
});

it('keeps the table blocked during the preparation time after the release', function () {
    $this->seed(TableSeeder::class);
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $this->travelTo(now()->setTime(12, 0));

    $admin = walkInAdmin();
    $service = app(TableAssignmentService::class);

    $walkIn = $service->seatWalkIn(2, null, now(), true);

    $this->actingAs($admin)->patch(route('admin.walkins.release', $walkIn));

    // Le temps de préparation n'est pas écoulé : la table reste bloquée.
    $this->travelTo(now()->setTime(12, 5));
    expect($service->isSlotAvailable(now()->toDateString(), '12:00', 2))->toBeFalse();

    // Après le temps de préparation, la table redevient disponible.
    $this->travelTo(now()->setTime(12, 20));
    expect($service->isSlotAvailable(now()->toDateString(), '12:30', 2))->toBeTrue();
});

it('never blocks the next day', function () {
    $this->seed(TableSeeder::class);
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $this->travelTo(now()->setTime(12, 0));

    $service = app(TableAssignmentService::class);

    $walkIn = $service->seatWalkIn(2, null, now(), true);

    expect($walkIn)->not->toBeNull();

    // Le lendemain, la table est de nouveau réservable.
    expect($service->isSlotAvailable(now()->addDay()->toDateString(), '12:00', 2))->toBeTrue();
});

it('blocks the table until the end of the service when the session is never closed', function () {
    $this->seed(TableSeeder::class);
    Table::where('number', '!=', 1)->update(['is_active' => false]);

    $this->travelTo(now()->setTime(12, 0));

    $service = app(TableAssignmentService::class);

    $walkIn = $service->seatWalkIn(2, null, now(), true);

    // Fin du blocage : fin de service + temps de préparation.
    $expected = now()->setTime(12, 0)->copy()
        ->setTimeFromTimeString((string) config('reservations.closing_time', '22:00'))
        ->addMinutes((int) config('reservations.buffer_minutes', 15));

    expect($walkIn->blockingEnd()->toDateTimeString())->toBe($expected->toDateTimeString())
        ->and($walkIn->serviceEnd()->format('H:i'))->toBe('22:00');
});

it('refuses to release a walk-in twice', function () {
    $this->seed(TableSeeder::class);

    $this->travelTo(now()->setTime(12, 0));

    $admin = walkInAdmin();
    $service = app(TableAssignmentService::class);

    $walkIn = $service->seatWalkIn(2, null, now(), true);

    $this->actingAs($admin)->patch(route('admin.walkins.release', $walkIn))->assertSessionHas('success');

    $this->actingAs($admin)
        ->patch(route('admin.walkins.release', $walkIn))
        ->assertSessionHas('error');
});

it('refuses to install a walk-in when no table can host the party', function () {
    $this->seed(TableSeeder::class);
    Table::query()->update(['is_active' => false]);

    $admin = walkInAdmin();

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), ['guests_count' => 2])
        ->assertSessionHas('error');

    expect(WalkIn::count())->toBe(0);
});

it('installs a walk-in on the smallest suitable table', function () {
    $this->seed(TableSeeder::class);

    $this->travelTo(now()->setTime(12, 0));

    $admin = walkInAdmin();

    $this->actingAs($admin)
        ->post(route('admin.walkins.store'), ['guests_count' => 2])
        ->assertSessionHas('success');

    $walkIn = WalkIn::first();

    expect($walkIn)->not->toBeNull()
        ->and($walkIn->table->number)->toBe(1);
});
