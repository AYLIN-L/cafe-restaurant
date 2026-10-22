<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\TableAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private TableAssignmentService $assignment
    ) {}

    // Affiche, recherche et filtre la liste des réservations.
    public function index(Request $request)
    {
        // Filtre de statut : seules les valeurs autorisées sont prises en compte.
        $status = $request->input('status');

        // « expired » et « hidden » sont des états dérivés, pas des statuts
        // stockés : ils sont traités à part.
        $onlyExpired = $status === 'expired';
        $onlyHidden = $status === 'hidden';

        if ($onlyExpired || $onlyHidden) {
            $status = null;
        } elseif (! in_array($status, Reservation::STATUSES, true)) {
            $status = null;
        }

        // Recherche : nettoyée et limitée à 100 caractères.
        $search = trim((string) $request->input('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $reservations = Reservation::with(['user', 'table'])
            ->withCount(['user as absences_count' => function ($query) {
                $query->where('attendance', Reservation::ATTENDANCE_ABSENT)
                    ->whereNull('hidden_at');
            }])
            ->when($onlyHidden, fn ($query) => $query->hidden(), fn ($query) => $query->visible())
            ->when($onlyExpired, fn ($query) => $query->whereNotNull('expired_at'))
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest() // Trie les résultats du plus récent au plus ancien
            ->paginate(10)
            ->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }

    // Valide et met à jour le statut d'une réservation.
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Reservation::STATUSES)],
        ]);

        $target = $data['status'];

        // Confirmer une réservation déjà confirmée ne doit rien changer : on
        // conserve la table déjà attribuée (double clic, rejeu de requête).
        if ($target === Reservation::STATUS_CONFIRMED && $reservation->status === Reservation::STATUS_CONFIRMED) {
            return $this->confirm($reservation);
        }

        // Transitions strictes : par exemple, une réservation annulée ne peut
        // pas repasser en attente ni être confirmée.
        $allowed = (array) config('reservations.transitions.'.$reservation->status, []);

        if (! in_array($target, $allowed, true)) {
            return back()->with('error', 'Ce changement de statut n\'est pas autorisé à partir de « '.$this->label($reservation->status).' ».');
        }

        // La confirmation déclenche l'attribution automatique d'une table.
        if ($target === Reservation::STATUS_CONFIRMED) {
            return $this->confirm($reservation);
        }

        // Tout autre statut libère la table attribuée et la journée du client.
        $reservation->update([
            'status' => $target,
            'table_id' => null,
            'active_date' => null,
        ]);

        return back()->with('success', 'La réservation est maintenant « '.$this->label($target).' ».');
    }

    /**
     * Enregistre la présence du client.
     *
     * Seule une réservation CONFIRMÉE peut être marquée : une demande
     * refusée, annulée ou expirée ne peut jamais être assimilée à une
     * absence.
     */
    public function attendance(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'attendance' => ['required', Rule::in([
                Reservation::ATTENDANCE_PRESENT,
                Reservation::ATTENDANCE_ABSENT,
                Reservation::ATTENDANCE_EXCUSED,
            ])],
        ], [
            'attendance.in' => 'La présence enregistrée n\'est pas valide.',
        ]);

        if (! $reservation->canRecordAttendance()) {
            return back()->with('error', 'Seule une réservation confirmée peut être marquée comme présence ou absence.');
        }

        $reservation->update(['attendance' => $data['attendance']]);

        // Blocage automatique lorsque le client atteint le nombre
        // d'absences vérifiées autorisé.
        $blocked = $reservation->user?->applyAutomaticBookingBlock() ?? false;

        if ($blocked) {
            return back()->with('success', 'La présence a été enregistrée. Ce client atteint le nombre d\'absences autorisées : ses nouvelles réservations sont bloquées.');
        }

        return back()->with('success', 'La présence a été enregistrée.');
    }

    /**
     * Masque une réservation. Elle n'est JAMAIS supprimée : l'historique et
     * les statistiques restent exacts.
     */
    public function hide(Reservation $reservation)
    {
        if (! $reservation->isHideable()) {
            return back()->with('error', 'Seule une réservation terminée, refusée, annulée ou expirée peut être masquée.');
        }

        $reservation->update([
            'hidden_at' => now(),
            'hidden_by' => auth()->id(),
        ]);

        return back()->with('success', 'La réservation a été masquée. Elle reste conservée dans l\'historique et les statistiques.');
    }

    // Remet une réservation masquée dans la liste.
    public function restore(Reservation $reservation)
    {
        if ($reservation->hidden_at === null) {
            return back()->with('success', 'Cette réservation est déjà visible.');
        }

        $reservation->update([
            'hidden_at' => null,
            'hidden_by' => null,
        ]);

        return back()->with('success', 'La réservation a été de nouveau affichée.');
    }

    // Libellé français d'un statut (pour les messages).
    private function label(string $status): string
    {
        return (string) (config('reservations.statuses.'.$status.'.label') ?? $status);
    }

    // Confirme la réservation et attribue la plus petite table disponible.
    protected function confirm(Reservation $reservation)
    {
        try {
            $result = DB::transaction(function () use ($reservation) {
                /** @var Reservation $reservation */
                $reservation = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

                // Déjà confirmée : on conserve la table existante (idempotent).
                if ($reservation->status === Reservation::STATUS_CONFIRMED) {
                    return ['already' => true];
                }

                // La réservation prend possession de la journée du client si
                // elle ne l'occupe pas déjà (doublon historique).
                $reservation->claimActiveDate();

                // Les tables candidates sont verrouillées : deux confirmations
                // simultanées ne peuvent pas attribuer la même table.
                $table = $this->assignment->findTableForSlot(
                    $reservation->reservation_date,
                    $reservation->reservation_time,
                    $reservation->guests_count,
                    $reservation->id,
                    true,
                );

                if ($table === null) {
                    return ['unavailable' => true];
                }

                $reservation->update([
                    'status' => Reservation::STATUS_CONFIRMED,
                    'table_id' => $table->id,
                ]);

                return ['number' => $table->number];
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Une erreur est survenue lors de la confirmation de la réservation.');
        }

        if ($result['unavailable'] ?? false) {
            return back()->with('error', "Aucune table disponible pour ce créneau. Vérifiez les capacités, l'état des tables ou libérez une table occupée.");
        }

        if ($result['already'] ?? false) {
            return back()->with('success', 'La réservation est déjà confirmée.');
        }

        return back()->with('success', "Réservation confirmée. Table n°{$result['number']} attribuée.");
    }
}
