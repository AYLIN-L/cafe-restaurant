<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    // Enregistre le message du client connecté.
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        auth()->user()->contactMessages()->create($data);

        return back()->with('success', 'Votre message a bien été envoyé.');
    }
}
