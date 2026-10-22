<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Page d'administration des clients : recherche, filtres, blocage.
 */
function usersAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function usersClient(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'client'], $attributes));
}

it('renders the clients page for an admin', function () {
    $admin = usersAdmin();
    $client = usersClient(['name' => 'Camille Durand']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Camille Durand')
        ->assertSee('Gestion des clients');
});

it('forbids a client from accessing the clients page', function () {
    $client = usersClient();

    $this->actingAs($client)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('requires authentication on the clients page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

it('searches clients by name and email', function () {
    $admin = usersAdmin();
    usersClient(['name' => 'Camille Durand', 'email' => 'camille@example.com']);
    usersClient(['name' => 'Hugo Martin', 'email' => 'hugo@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Camille']))
        ->assertOk()
        ->assertSee('Camille Durand')
        ->assertDontSee('Hugo Martin');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'hugo@example.com']))
        ->assertOk()
        ->assertSee('Hugo Martin')
        ->assertDontSee('Camille Durand');
});

it('filters clients on their booking block', function () {
    $admin = usersAdmin();
    $blocked = usersClient(['name' => 'Client bloqué']);
    $allowed = usersClient(['name' => 'Client autorisé']);

    $blocked->blockBooking();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['filter' => 'blocked']))
        ->assertOk()
        ->assertSee('Client bloqué')
        ->assertDontSee('Client autorisé');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['filter' => 'unblocked']))
        ->assertOk()
        ->assertSee('Client autorisé')
        ->assertDontSee('Client bloqué');
});

it('displays the number of verified absences of a client', function () {
    $admin = usersAdmin();
    $client = usersClient(['name' => 'Client absent']);

    Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'attendance' => Reservation::ATTENDANCE_ABSENT,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Client absent')
        ->assertSee('1 / 5');
});

it('blocks and unblocks a client from the page', function () {
    $admin = usersAdmin();
    $client = usersClient();

    $this->actingAs($admin)
        ->patch(route('admin.users.block', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.users.unblock', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->isBookingBlocked())->toBeFalse();
});

it('refuses to delete a client who still has reservations', function () {
    $admin = usersAdmin();
    $client = usersClient();

    Reservation::create([
        'user_id' => $client->id,
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
    $admin = usersAdmin();
    $client = usersClient();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $client))
        ->assertSessionHas('success');

    expect(User::find($client->id))->toBeNull();
});
