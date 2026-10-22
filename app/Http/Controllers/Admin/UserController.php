<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Affiche les clients : recherche, pagination et état des blocages.
    public function index(Request $request)
    {
        // Recherche : nettoyée et limitée à 100 caractères.
        $search = trim((string) $request->input('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        // Filtre : « blocked » ou «unblocked ».
        $filter = $request->input('filter');
        $filter = in_array($filter, ['blocked', 'unblocked'], true) ? $filter : null;

        $users = User::query()
            ->where('role', 'client')
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($filter === 'blocked', fn ($query) => $query->whereNotNull('booking_blocked_at'))
            ->when($filter === 'unblocked', fn ($query) => $query->whereNull('booking_blocked_at'))
            ->withCount([
                'reservations',
                // Absences vérifiées : le compteur est toujours dérivé des
                // réservations, il ne peut donc pas être compté deux fois.
                'reservations as absences_count' => fn ($query) => $query
                    ->where('attendance', Reservation::ATTENDANCE_ABSENT)
                    ->whereNull('hidden_at'),
            ])
            ->withMax('reservations', 'reservation_date')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $totalClients = User::where('role', 'client')->count();
        $blockedClients = User::where('role', 'client')->whereNotNull('booking_blocked_at')->count();

        return view('admin.users.index', compact('users', 'search', 'filter', 'totalClients', 'blockedClients'));
    }

    // Bloque les nouvelles réservations d'un client.
    public function block(User $user)
    {
        if (! $this->isManageable($user)) {
            abort(403);
        }

        if ($user->isBookingBlocked()) {
            return back()->with('error', 'Ce client est déjà bloqué.');
        }

        $user->blockBooking();

        return back()->with('success', 'Les nouvelles réservations de '.$user->name.' sont bloquées.');
    }

    /**
     * Débloque les nouvelles réservations d'un client.
     *
     * Le client est marqué comme explicitement exempté : le seuil
     * automatique d'absences ne le rebloquera pas.
     */
    public function unblock(User $user)
    {
        if (! $this->isManageable($user)) {
            abort(403);
        }

        if (! $user->isBookingBlocked()) {
            return back()->with('error', 'Ce client n\'est pas bloqué.');
        }

        $user->unblockBooking();

        return back()->with('success', 'Les nouvelles réservations de '.$user->name.' sont de nouveau autorisées.');
    }

    // Supprime un client, mais protège les comptes administrateurs et les
    // clients qui possèdent encore des réservations.
    public function destroy(User $user)
    {
        if ($user->isAdmin() || $user->id === auth()->id()) {
            abort(403);
        }

        if ($user->reservations()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce client : il possède des réservations. Annulez-les d\'abord.');
        }

        $user->delete();

        return back()->with('success', 'Le client a bien été supprimé.');
    }

    /** Le client peut-il être bloqué ou débloqué par l'administration ? */
    private function isManageable(User $user): bool
    {
        return ! $user->isAdmin() && $user->id !== auth()->id();
    }
}
