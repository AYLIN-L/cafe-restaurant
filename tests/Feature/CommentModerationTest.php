<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Menu;
use App\Models\User;

/**
 * Modération des avis.
 *
 * Seuls les avis approuvés sont visibles publiquement. Un avis rejeté reste
 * consultable par l'administration et peut être remis en attente.
 */
function moderationAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function moderationClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

function moderationMenu(): Menu
{
    $category = Category::create(['name' => 'Plats', 'slug' => 'plats-'.uniqid()]);

    return Menu::create([
        'category_id' => $category->id,
        'title' => 'Tarte maison',
        'description' => 'Une tarte acidulée',
        'price' => 12,
        'is_available' => true,
    ]);
}

function moderationComment(User $user, Menu $menu, array $attributes = []): Comment
{
    return Comment::create(array_merge([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'content' => 'Une très bonne expérience',
        'rating' => 5,
        'is_approved' => false,
    ], $attributes));
}

it('keeps a new review pending and invisible', function () {
    $menu = moderationMenu();
    $client = moderationClient();

    $this->actingAs($client)
        ->post(route('comments.store', $menu), [
            'content' => 'Une très bonne expérience',
            'rating' => 5,
        ])
        ->assertSessionHas('success');

    $comment = Comment::first();

    expect($comment)->not->toBeNull()
        ->and($comment->isApproved())->toBeFalse()
        ->and($comment->isPendingModeration())->toBeTrue()
        ->and($comment->moderationLabel())->toBe('En attente');

    // L'avis n'est pas visible publiquement.
    $this->get(route('menu.show', $menu))->assertOk()->assertDontSee('Une très bonne expérience');
});

it('publishes an approved review', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $comment = moderationComment($client, $menu);

    $this->actingAs($admin)
        ->patch(route('admin.comments.approve', $comment))
        ->assertSessionHas('success');

    $comment->refresh();

    expect($comment->isApproved())->toBeTrue()
        ->and($comment->moderated_at)->not->toBeNull()
        ->and($comment->moderated_by)->toBe($admin->id)
        ->and($comment->moderationLabel())->toBe('Approuvé');

    $this->get(route('menu.show', $menu))->assertOk()->assertSee('Une très bonne expérience');
});

it('hides a rejected review and keeps the reason', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $comment = moderationComment($client, $menu);

    $this->actingAs($admin)
        ->patch(route('admin.comments.reject', $comment), ['moderation_note' => 'Contenu inapproprié'])
        ->assertSessionHas('success');

    $comment->refresh();

    expect($comment->isApproved())->toBeFalse()
        ->and($comment->isRejected())->toBeTrue()
        ->and($comment->moderation_note)->toBe('Contenu inapproprié')
        ->and($comment->moderated_by)->toBe($admin->id);

    $this->get(route('menu.show', $menu))->assertOk()->assertDontSee('Une très bonne expérience');
});

it('returns a review to the moderation queue', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $comment = moderationComment($client, $menu, [
        'is_approved' => false,
        'moderated_at' => now(),
        'moderation_note' => 'Contenu inapproprié',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.comments.restore', $comment))
        ->assertSessionHas('success');

    $comment->refresh();

    expect($comment->isPendingModeration())->toBeTrue()
        ->and($comment->moderated_at)->toBeNull()
        ->and($comment->moderation_note)->toBeNull();

    $this->get(route('menu.show', $menu))->assertOk()->assertDontSee('Une très bonne expérience');
});

it('deletes a review only when the admin asks for it', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $comment = moderationComment($client, $menu);

    $this->actingAs($admin)
        ->delete(route('admin.comments.destroy', $comment))
        ->assertSessionHas('success');

    expect(Comment::find($comment->id))->toBeNull();
});

it('forbids a client from moderating reviews', function () {
    $menu = moderationMenu();
    $client = moderationClient();

    $comment = moderationComment($client, $menu);

    $this->actingAs($client)
        ->patch(route('admin.comments.approve', $comment))
        ->assertForbidden();

    expect($comment->fresh()->isApproved())->toBeFalse();
});

it('filters the moderation page by state', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $pending = moderationComment($client, $menu, ['content' => 'Avis en attente']);
    $approved = moderationComment($client, $menu, ['content' => 'Avis approuvé', 'is_approved' => true]);
    $rejected = moderationComment($client, $menu, ['content' => 'Avis rejeté', 'moderated_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.comments.index'))
        ->assertOk()
        ->assertSee('Avis en attente')
        ->assertSee('Avis approuvé')
        ->assertSee('Avis rejeté');

    $this->actingAs($admin)
        ->get(route('admin.comments.index', ['filter' => 'approved']))
        ->assertOk()
        ->assertSee('Avis approuvé')
        ->assertDontSee('Avis en attente')
        ->assertDontSee('Avis rejeté');

    $this->actingAs($admin)
        ->get(route('admin.comments.index', ['filter' => 'pending']))
        ->assertOk()
        ->assertSee('Avis en attente')
        ->assertDontSee('Avis approuvé');

    $this->actingAs($admin)
        ->get(route('admin.comments.index', ['filter' => 'rejected']))
        ->assertOk()
        ->assertSee('Avis rejeté')
        ->assertDontSee('Avis en attente');

    // Recherche par mot-clé.
    $this->actingAs($admin)
        ->get(route('admin.comments.index', ['search' => 'approuvé']))
        ->assertOk()
        ->assertSee('Avis approuvé')
        ->assertDontSee('Avis en attente');
});

it('refuses a rejection reason longer than the column allows', function () {
    $menu = moderationMenu();
    $admin = moderationAdmin();
    $client = moderationClient();

    $comment = moderationComment($client, $menu);

    $this->actingAs($admin)
        ->patch(route('admin.comments.reject', $comment), ['moderation_note' => str_repeat('a', 256)])
        ->assertSessionHasErrors('moderation_note');

    expect($comment->fresh()->isPendingModeration())->toBeTrue();
});

it('counts only pending reviews in the moderation dashboard', function () {
    $menu = moderationMenu();
    $client = moderationClient();

    moderationComment($client, $menu, ['content' => 'En attente']);
    moderationComment($client, $menu, ['content' => 'Approuvé', 'is_approved' => true]);
    moderationComment($client, $menu, ['content' => 'Rejeté', 'moderated_at' => now()]);

    expect(Comment::pendingModeration()->count())->toBe(1)
        ->and(Comment::approved()->count())->toBe(1)
        ->and(Comment::rejected()->count())->toBe(1);
});
