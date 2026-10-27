<?php

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;

/**
 * Filtres de la page admin des réservations : statuts dérivés (« Terminée »,
 * « Expirée »), combinaison recherche + filtre + pagination, survie du filtre
 * après une action, et paramètre « page » périmé.
 */

function filtersAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function filtersClient(string $name): User
{
    return User::factory()->create(['role' => 'client', 'name' => $name]);
}

function filtersMake(User $client, array $attributes = []): Reservation
{
    return Reservation::create(array_merge([
        'user_id' => $client->id,
        'table_id' => null,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => null,
    ], $attributes));
}

it('offers the Terminée filter and shows only ended confirmed reservations under it', function () {
    $admin = filtersAdmin();

    $ended = filtersMake(filtersClient('Terminée Alice'), [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
    ]);

    $running = filtersMake(filtersClient('En cours Bob'), [
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $cancelled = filtersMake(filtersClient('Annulée Céline'), [
        'status' => Reservation::STATUS_CANCELLED,
        'reservation_date' => now()->subDay()->toDateString(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'completed']))
        ->assertOk();

    // L'option « Terminée » est proposée dans le filtre.
    $response->assertSee('<option value="completed"', false);

    // Seul le créneau réellement écoulé apparaît : pas la réservation
    // encore en cours, pas la réservation annulée.
    $response->assertSee('Terminée Alice')
        ->assertDontSee('En cours Bob')
        ->assertDontSee('Annulée Céline');

    expect($ended->fresh()->displayStatus())->toBe('completed')
        ->and($running->fresh()->displayStatus())->toBe('confirmed')
        ->and($cancelled->fresh()->displayStatus())->toBe('cancelled');
});

it('does not count an expired confirmed reservation under the Terminée filter', function () {
    $admin = filtersAdmin();
    filtersMake(filtersClient('Expirée Dorothée'), [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'expired_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'completed']))
        ->assertOk()
        ->assertDontSee('Expirée Dorothée');
});

it('shows pending requests past the expiration delay under the Expirée filter even before the scheduled command runs', function () {
    $admin = filtersAdmin();

    $late = filtersMake(filtersClient('Tardive Émile'));
    $late->forceFill(['created_at' => now()->subHours(25)])->save();

    $fresh = filtersMake(filtersClient('Récente Fatima'));

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'expired']))
        ->assertOk();

    $response->assertSee('Tardive Émile')
        ->assertDontSee('Récente Fatima');

    // La demande n'a PAS changé de statut en base : le passage en
    // « cancelled + expired_at » reste le travail de la commande planifiée.
    expect($late->fresh()->status)->toBe(Reservation::STATUS_PENDING)
        ->and($late->fresh()->expired_at)->toBeNull();
});

it('shows an explicitly expired request under the Expirée filter', function () {
    $admin = filtersAdmin();
    filtersMake(filtersClient('Horodatée Ghislain'), [
        'status' => Reservation::STATUS_CANCELLED,
        'reservation_date' => now()->subDays(3)->toDateString(),
        'expired_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'expired']))
        ->assertOk()
        ->assertSee('Horodatée Ghislain');
});

it('keeps the active filter after confirming a reservation', function () {
    $admin = filtersAdmin();
    $reservation = filtersMake(filtersClient('Filtrée Hugo'));

    // L'administrateur consulte la liste filtrée « En attente »...
    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'pending']))
        ->assertOk();

    // ... puis confirme depuis cette page : la redirection conserve le filtre.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertRedirect(route('admin.reservations.index', ['status' => 'pending']));
});

it('clamps a stale page parameter instead of showing an empty filtered list', function () {
    $admin = filtersAdmin();

    // 11 demandes en attente : la page 2 existe et ne contient que la
    // plus ancienne.
    $clients = collect(range(1, 11))->map(fn ($i) => filtersMake(filtersClient('Page Ivan '.$i)));

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'pending', 'page' => 3]))
        ->assertOk()
        // page=3 n'existe plus : on retombe sur la dernière page occupée
        // (la page 2, qui ne contient que la plus ancienne), pas sur une
        // liste vide.
        ->assertSee('Page Ivan 1')
        ->assertDontSee('Page Ivan 5');
});

it('combines search, status filter and pagination with preserved query parameters', function () {
    $admin = filtersAdmin();

    foreach (range(1, 12) as $i) {
        filtersMake(filtersClient('Alpha N°'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)));
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'pending', 'search' => 'Alpha', 'page' => 2]))
        ->assertOk();

    // 12 résultats, 10 par page : la page 2 contient les n°11 et 12.
    $response->assertSee('Alpha N°11')
        ->assertSee('Alpha N°12');

    // Les liens de pagination préservent la recherche et le filtre.
    expect(str_contains((string) $response->getContent(), 'status=pending'))->toBeTrue()
        ->and(str_contains((string) $response->getContent(), 'search=Alpha'))->toBeTrue();
});

it('excludes hidden reservations from every status filter except the Masquées filter', function () {
    $admin = filtersAdmin();

    filtersMake(filtersClient('Visible Julia'), [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'attendance' => Reservation::ATTENDANCE_PRESENT,
    ]);

    filtersMake(filtersClient('Cachée Kader'), [
        'status' => Reservation::STATUS_CONFIRMED,
        'reservation_date' => now()->subDay()->toDateString(),
        'attendance' => Reservation::ATTENDANCE_PRESENT,
        'hidden_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'completed']))
        ->assertOk()
        ->assertSee('Visible Julia')
        ->assertDontSee('Cachée Kader');

    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertSee('Visible Julia')
        ->assertDontSee('Cachée Kader');

    $this->actingAs($admin)
        ->get(route('admin.reservations.index', ['status' => 'hidden']))
        ->assertOk()
        ->assertSee('Cachée Kader')
        ->assertDontSee('Visible Julia');
});
