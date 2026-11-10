<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Affiche, recherche et filtre les messages envoyés via le formulaire
     * de contact public. Le défaut : les plus récents d'abord, tous statuts.
     */
    public function index(Request $request)
    {
        // Filtre de statut : seules les valeurs autorisées sont prises en compte.
        $status = $request->input('status');
        $status = in_array($status, ContactMessage::STATUSES, true) ? $status : null;

        // Recherche : nettoyée et limitée à 100 caractères.
        $search = trim((string) $request->input('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $query = ContactMessage::query()
            ->with('user')
            ->when($status, fn ($q, $value) => $q->where('status', $value))
            ->when($search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('subject', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%');
                });
            })
            ->latest();

        $messages = $query->paginate(12)
            ->withQueryString();

        // Un paramètre « page » devenu périmètre (message supprimé, filtre
        // resserré...) ne doit pas donner l'impression que le filtre est
        // cassé : on revient à la dernière page réellement occupée.
        if ($messages->isEmpty() && $messages->currentPage() > 1) {
            $messages = $query->paginate(12, ['*'], 'page', $messages->lastPage())
                ->withQueryString();
        }

        $stats = [
            ContactMessage::STATUS_UNREAD => ContactMessage::unread()->count(),
            ContactMessage::STATUS_READ => ContactMessage::unprocessed()->where('status', ContactMessage::STATUS_READ)->count(),
            ContactMessage::STATUS_PROCESSED => ContactMessage::where('status', ContactMessage::STATUS_PROCESSED)->count(),
        ];

        return view('admin.contacts.index', compact('messages', 'status', 'search', 'stats'));
    }

    /**
     * Détail complet d'un message.
     *
     * Ouvrir un message non lu le passe automatiquement « lu » : c'est le
     * comportement d'une boîte de réception, et le compteur « Non lus »
     * reste synchronisé sans action supplémentaire. Le message reste
     * « à traiter » (donc compté par la notification) tant qu'il n'a pas
     * été explicitement marqué « traité ».
     */
    public function show(ContactMessage $contactMessage)
    {
        if ($contactMessage->isUnread()) {
            $contactMessage->update(['status' => ContactMessage::STATUS_READ]);
        }

        return view('admin.contacts.show', ['contactMessage' => $contactMessage]);
    }

    // Met à jour le statut (non lu, lu, traité) après validation serveur.
    public function updateStatus(Request $request, ContactMessage $contactMessage)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', ContactMessage::STATUSES)],
        ], [
            'status.required' => 'Le statut demandé est manquant.',
            'status.in' => 'Le statut demandé n\'est pas valide.',
        ]);

        $target = $data['status'];

        // Idempotence : re-marquer « traité » un message déjà traité ne
        // doit rien changer (double clic, rejeu de requête).
        if ($target === $contactMessage->status) {
            return back()->with('success', 'Ce message est déjà « '.$contactMessage->statusLabel().' ».');
        }

        $contactMessage->update(['status' => $target]);

        return back()->with('success', 'Le message est maintenant « '.ContactMessage::statusLabelFor($target).' ».');
    }

    // Supprime définitivement un message (action protégée par confirmation).
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Le message a bien été supprimé.');
    }
}
