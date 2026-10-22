<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Affiche les avis : filtres (en attente, approuvés, rejetés) et recherche.
    public function index(Request $request)
    {
        // Filtre d'état de modération.
        $filter = $request->input('filter');
        $filter = in_array($filter, [Comment::MODERATION_PENDING, Comment::MODERATION_APPROVED, Comment::MODERATION_REJECTED], true)
            ? $filter
            : null;

        // Recherche : nettoyée et limitée à 100 caractères.
        $search = trim((string) $request->input('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $comments = Comment::with(['user', 'menu', 'moderator'])
            ->when($filter === Comment::MODERATION_PENDING, fn ($query) => $query->pendingModeration())
            ->when($filter === Comment::MODERATION_APPROVED, fn ($query) => $query->approved())
            ->when($filter === Comment::MODERATION_REJECTED, fn ($query) => $query->rejected())
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('content', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            Comment::MODERATION_PENDING => Comment::pendingModeration()->count(),
            Comment::MODERATION_APPROVED => Comment::approved()->count(),
            Comment::MODERATION_REJECTED => Comment::rejected()->count(),
        ];

        return view('admin.comments.index', compact('comments', 'filter', 'search', 'stats'));
    }

    // Approuve un avis : il devient visible publiquement.
    public function approve(Comment $comment)
    {
        if ($comment->isApproved()) {
            return back()->with('error', 'Cet avis est déjà approuvé.');
        }

        $comment->update([
            'is_approved' => true,
            'moderated_at' => now(),
            'moderated_by' => auth()->id(),
            'moderation_note' => null,
        ]);

        return back()->with('success', 'L\'avis a bien été approuvé : il est maintenant visible publiquement.');
    }

    /**
     * Rejette un avis : il n'est plus visible publiquement.
     *
     * L'avis n'est jamais supprimé : il reste consultable par
     * l'administration et peut être remis en attente de modération.
     */
    public function reject(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'moderation_note' => ['nullable', 'string', 'max:255'],
        ], [
            'moderation_note.max' => 'Le motif ne peut pas dépasser 255 caractères.',
        ]);

        $comment->update([
            'is_approved' => false,
            'moderated_at' => now(),
            'moderated_by' => auth()->id(),
            'moderation_note' => $data['moderation_note'] ?? null,
        ]);

        return back()->with('success', 'L\'avis a été rejeté : il n\'est plus visible publiquement.');
    }

    // Remet un avis en attente de modération.
    public function restore(Comment $comment)
    {
        if ($comment->isPendingModeration()) {
            return back()->with('error', 'Cet avis est déjà en attente de modération.');
        }

        $comment->update([
            'is_approved' => false,
            'moderated_at' => null,
            'moderated_by' => null,
            'moderation_note' => null,
        ]);

        return back()->with('success', 'L\'avis est de nouveau en attente de modération.');
    }

    // Supprime définitivement un avis.
    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back()->with('success', 'L\'avis a bien été supprimé.');
    }
}
