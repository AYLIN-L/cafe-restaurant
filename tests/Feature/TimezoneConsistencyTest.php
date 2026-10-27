<?php

use App\Models\Table;
use App\Models\User;
use App\Services\RestaurantSchedule;
use App\Services\TableAssignmentService;
use Illuminate\Support\Facades\Schema;

/**
 * Cohérence horaire : l'application affiche et calcule l'heure du
 * restaurant (Africa/Casablanca), jamais l'heure locale du poste.
 * Les créneaux passés sont indisponibles, les créneaux à venir
 * disponibles, quelle que soit l'heure du serveur.
 */

it('uses the Africa/Casablanca restaurant timezone everywhere', function () {
    expect(config('app.timezone'))->toBe('Africa/Casablanca')
        ->and(config('restaurant.timezone'))->toBe('Africa/Casablanca');

    $schedule = app(RestaurantSchedule::class);

    expect($schedule->timezone())->toBe('Africa/Casablanca')
        ->and($schedule->now()->timezone->getName())->toBe('Africa/Casablanca')
        ->and(now()->timezone->getName())->toBe('Africa/Casablanca');
});

it('marks past slots of a free day as unavailable and future slots as available', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Table::create(['number' => 1, 'capacity' => 4, 'is_active' => true]);

    /** @var TableAssignmentService $assignment */
    $assignment = app(TableAssignmentService::class);

    // Hier : tous les créneaux sont passés, même le premier (08:00).
    $past = collect($assignment->availableSlots(now()->subDay()->toDateString(), 2))
        ->firstWhere('time', '08:00');

    // Demain : le premier créneau (08:00) est disponible.
    $future = collect($assignment->availableSlots(now()->addDay()->toDateString(), 2))
        ->firstWhere('time', '08:00');

    expect($past['available'])->toBeFalse()
        ->and($future['available'])->toBeTrue();
});

it('defaults the walk-in arrival time to the current restaurant time', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Table::create(['number' => 1, 'capacity' => 4, 'is_active' => true]);

    $response = $this->actingAs($admin)
        ->post(route('admin.walkins.store'), ['guests_count' => 2])
        ->assertRedirect();

    $walkIn = \App\Models\WalkIn::query()->sole();

    // L'arrivée est « maintenant », à la minute près, dans le fuseau du
    // restaurant : aucun décalage d'une heure ne doit subsister.
    expect(abs($walkIn->seated_at->diffInSeconds(now())))->toBeLessThan(5.0)
        ->and($walkIn->seated_at->timezone->getName())->toBe('Africa/Casablanca')
        // La table reste bloquée jusqu'à libération explicite.
        ->and($walkIn->released_at)->toBeNull();

    // Le message confirme l'installation.
    $response->assertSessionHas('success', fn ($message) => str_contains($message, 'Client installé à la table n°1.'));
});

it('renders the arrival-time field above its explanatory text in the walk-in form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Table::create(['number' => 1, 'capacity' => 4, 'is_active' => true]);

    $content = (string) $this->actingAs($admin)
        ->get(route('admin.tables.index'))
        ->assertOk()
        ->getContent();

    $fieldPosition = mb_strpos($content, 'id="seated_time"');
    $hintPosition = mb_strpos($content, "Par d");

    // Le champ précède le paragraphe explicatif, qui reste juste en dessous.
    expect($fieldPosition)->toBeInt()->not->toBeFalse()
        ->and($hintPosition)->toBeInt()->not->toBeFalse()
        ->and($fieldPosition)->toBeLessThan($hintPosition);
});

it('keeps the reservations table schema intact', function () {
    // Filet de sécurité : les colonnes du cycle de vie doivent exister.
    $columns = Schema::getColumnListing('reservations');

    foreach (['active_date', 'attendance', 'expired_at', 'hidden_at', 'hidden_by'] as $column) {
        expect($columns)->toContain($column);
    }
});
