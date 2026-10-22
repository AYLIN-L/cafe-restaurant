<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Enregistre un avis qui restera en attente de modération.
    public function store(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        $menu->comments()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
            'rating' => $data['rating'],
            'is_approved' => false,
        ]);

        return back()->with('success', 'Merci ! Votre avis sera publié après modération.');
    }
}
