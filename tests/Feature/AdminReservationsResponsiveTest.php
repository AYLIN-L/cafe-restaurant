<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Rendu responsive et pagination de la page admin des réservations :
 * le tableau n'apparaît qu'à partir de « md », les cartes qu'en dessous,
 * et la pagination partagée « coffee » est utilisée.
 */

function responsiveAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function responsiveClient(string $name): User
{
    return User::factory()->create(['role' => 'client', 'name' => $name]);
}

function responsiveMake(User $client, array $attributes = []): Reservation
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

it('renders the table only on desktop and the cards only on mobile', function () {
    $admin = responsiveAdmin();
    responsiveMake(responsiveClient('Responsive Lucie'));

    $content = (string) $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->getContent();

    // Le tableau est masqué sous « md » (cartes seules sur mobile)...
    expect(str_contains($content, 'hidden rounded-2xl border border-coffee/10 bg-white shadow-sm md:block'))->toBeTrue()
        // ... et les cartes sont masquées à partir de « md ».
        ->and(str_contains($content, 'mt-6 space-y-4 md:hidden'))->toBeTrue()
        // Le tableau et les cartes coexistent dans le HTML, chacun avec sa
        // propre visibilité responsive.
        ->and(str_contains($content, 'min-w-[1120px]'))->toBeTrue();
});

it('renders the shared coffee pagination on the admin reservations page', function () {
    $admin = responsiveAdmin();

    foreach (range(1, 12) as $i) {
        responsiveMake(responsiveClient('Pagination Marie '.$i));
    }

    $content = (string) $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->getContent();

    // Style « coffee » : pastille active café sur fond crème, boutons ronds.
    expect(str_contains($content, 'rounded-full bg-coffee px-3 text-sm font-medium text-cream'))->toBeTrue()
        ->and(str_contains($content, 'Affichage'))->toBeTrue()
        ->and(str_contains($content, 'aria-current="page"'))->toBeTrue();
});
