<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Menu;
use App\Models\User;

function createMenu(Category $category, array $attributes = []): Menu
{
    return Menu::create(array_merge([
        'category_id' => $category->id,
        'title' => 'Plat',
        'description' => 'Description',
        'price' => 50,
        'is_available' => true,
    ], $attributes));
}

function approveReview(User $user, Menu $menu, int $rating): void
{
    Comment::create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'content' => 'Avis',
        'rating' => $rating,
        'is_approved' => true,
    ]);
}

it('ranks homepage dishes by average approved rating', function () {
    $category = Category::create(['name' => 'Plats', 'slug' => 'plats']);
    $user = User::factory()->create();

    $top = createMenu($category, ['title' => 'Plat top']);
    $second = createMenu($category, ['title' => 'Plat second']);
    $third = createMenu($category, ['title' => 'Plat trois']);
    $unrated = createMenu($category, ['title' => 'Plat sans avis']);

    approveReview($user, $top, 5);
    approveReview($user, $second, 4);
    approveReview($user, $third, 3);

    // Avis non approuvé : ne doit pas compter.
    Comment::create([
        'user_id' => $user->id,
        'menu_id' => $unrated->id,
        'content' => 'En attente',
        'rating' => 5,
        'is_approved' => false,
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSeeInOrder(['Plat top', 'Plat second', 'Plat trois']);
});

it('renders the full catalog page', function () {
    $category = Category::create(['name' => 'Desserts', 'slug' => 'desserts']);
    createMenu($category, ['title' => 'Tarte maison']);

    $response = $this->get('/menu');

    $response->assertOk();
    // Le titre est composé de deux éléments imbriqués (« Notre » + « carte »).
    $response->assertSeeInOrder(['Notre', 'carte'], false);
    $response->assertSee('Tarte maison');
});

it('filters the catalog by category', function () {
    $starters = Category::create(['name' => 'Entrées', 'slug' => 'entrees']);
    $desserts = Category::create(['name' => 'Desserts', 'slug' => 'desserts']);

    createMenu($starters, ['title' => 'Salade fraîche']);
    createMenu($desserts, ['title' => 'Tarte maison']);

    $response = $this->get('/menu?category=desserts');

    $response->assertOk();
    $response->assertSee('Tarte maison');
    $response->assertDontSee('Salade fraîche');
});

it('combines search with the category filter', function () {
    $desserts = Category::create(['name' => 'Desserts', 'slug' => 'desserts']);

    createMenu($desserts, ['title' => 'Tarte aux pommes']);
    createMenu($desserts, ['title' => 'Mousse au chocolat']);

    $response = $this->get('/menu?category=desserts&q=tarte');

    $response->assertOk();
    $response->assertSee('Tarte aux pommes');
    $response->assertDontSee('Mousse au chocolat');
});

it('shows an empty state when nothing matches', function () {
    $category = Category::create(['name' => 'Desserts', 'slug' => 'desserts']);
    createMenu($category, ['title' => 'Tarte maison']);

    $response = $this->get('/menu?q=introuvable');

    $response->assertOk();
    $response->assertSee('Aucun plat ne correspond');
});
