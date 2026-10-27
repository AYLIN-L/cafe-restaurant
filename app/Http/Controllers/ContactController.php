<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\RestaurantSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    /**
     * Affiche la page de contact publique.
     */
    public function index(RestaurantSchedule $schedule): View
    {
        $restaurant = config('restaurant');
        $mapQuery = trim((string) data_get($restaurant, 'address.map_query', ''));

        $mapEmbedUrl = $mapQuery !== ''
            ? 'https://www.google.com/maps?q='.rawurlencode($mapQuery).'&z=16&output=embed'
            : null;

        $directionsUrl = $mapQuery !== ''
            ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($mapQuery)
            : null;

        return view('contact.create', [
            'restaurant' => $restaurant,
            'status' => $schedule->status(),
            'hoursLabel' => $schedule->hoursLabel(),
            'mapEmbedUrl' => $mapEmbedUrl,
            'directionsUrl' => $directionsUrl,
        ]);
    }

    /**
     * Enregistre le message d'un visiteur ou d'un client connecté.
     *
     * Pour un utilisateur connecté, le nom et l'e-mail sont toujours lus
     * depuis le compte : les champs envoyés par le navigateur sont ignorés.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];

        if ($user === null) {
            $rules['name'] = ['required', 'string', 'max:100'];
            $rules['email'] = ['required', 'string', 'email', 'max:255'];
        }

        $data = $request->validate($rules, [
            'name.required' => 'Veuillez indiquer votre nom complet.',
            'name.string' => 'Le nom complet doit être un texte valide.',
            'name.max' => 'Le nom complet ne peut pas dépasser 100 caractères.',
            'email.required' => 'Veuillez indiquer votre adresse e-mail.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'email.max' => "L'adresse e-mail ne peut pas dépasser 255 caractères.",
            'subject.required' => 'Veuillez indiquer le sujet de votre message.',
            'subject.string' => 'Le sujet doit être un texte valide.',
            'subject.max' => 'Le sujet ne peut pas dépasser 255 caractères.',
            'message.required' => 'Veuillez écrire votre message.',
            'message.string' => 'Le message doit être un texte valide.',
            'message.max' => 'Le message ne peut pas dépasser 2 000 caractères.',
        ], [
            'name' => 'nom complet',
            'email' => 'adresse e-mail',
            'subject' => 'sujet',
            'message' => 'message',
        ]);

        try {
            ContactMessage::create([
                'user_id' => $user?->id,
                'name' => $user?->name ?? $data['name'] ?? null,
                'email' => $user?->email ?? $data['email'] ?? null,
                'subject' => $data['subject'],
                'message' => $data['message'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', "Une erreur est survenue lors de l'envoi de votre message. Merci de réessayer dans quelques instants.");
        }

        return back()->with(
            'success',
            'Merci ! Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.'
        );
    }
}
