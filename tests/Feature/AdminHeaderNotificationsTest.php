<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\User;

/**
 * Tests des notifications de l'en-tête de l'administration.
 *
 * Les compteurs sont calculés par un view composer (AppServiceProvider) :
 * ils sont donc disponibles sur toutes les pages de l'administration et
 * reposent sur la même logique que le tableau de bord.
 */
it('shows the pending counters in the admin header', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $category = Category::factory()->create();
    $menu = Menu::factory()->create(['category_id' => $category->id]);

    // Deux demandes en attente, un avis à modérer.
    foreach (range(1, 2) as $index) {
        Reservation::create([
            'user_id' => $client->id,
            'reservation_date' => now()->addDays($index)->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    Comment::create([
        'user_id' => $client->id,
        'menu_id' => $menu->id,
        'content' => 'Un très bon plat.',
        'rating' => 5,
        'is_approved' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('aria-label="2 réservations en attente"', false)
        ->assertSee('aria-label="1 commentaire à modérer"', false);

    // Les badges sont cliquables et mènent aux bonnes listes.
    $response->assertSee(route('admin.reservations.index', ['status' => 'pending']), false)
        ->assertSee(route('admin.comments.index', ['filter' => 'pending']), false);
});

it('displays the notifications on the left of the page title', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

    $notifications = strpos($html, 'title="Réservations en attente de traitement"');
    $title = strpos($html, 'sm:inline">Administration<');

    expect($notifications)->not->toBeFalse()
        ->and($title)->not->toBeFalse()
        ->and($notifications)->toBeLessThan($title);
});

it('shows the counters on every admin page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (['admin.dashboard', 'admin.tables.index', 'admin.menus.index'] as $route) {
        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk()
            ->assertSee('title="Réservations en attente de traitement"', false)
            ->assertSee('title="Commentaires à modérer"', false);
    }
});

it('counts only the reservations that still need a decision', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
    ]);

    $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

    // Aucune demande en attente : le compteur vaut zéro.
    expect($html)->toContain('aria-label="0 réservation en attente"');
});
