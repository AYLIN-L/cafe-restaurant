<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationAbsenceWarningMail;
use App\Mail\ReservationCancelledByAdminMail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationRejectedMail;
use App\Models\Reservation;
use App\Services\TableAssignmentService;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

        // « expired », « completed » et « hidden » sont des états dérivés, pas
        // des statuts stockés : ils sont traités à part.
        $onlyExpired = $status === 'expired';
        $onlyCompleted = $status === 'completed';
        $onlyHidden = $status === 'hidden';

        if ($onlyExpired || $onlyCompleted || $onlyHidden) {
            $status = null;
        } elseif (! in_array($status, Reservation::STATUSES, true)) {
            $status = null;
        }

        // Recherche : nettoyée et limitée à 100 caractères.
        $search = trim((string) $request->input('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        // Borne du statut dérivé « completed » : un créneau est terminé dès
        // que son DÉBUT précède « maintenant − (durée + temps de
        // préparation) ». La comparaison porte sur la concaténation
        // date + heure ; l'expression SQL dépend du moteur (SQLite en tests,
        // MySQL en production).
        $completedCutoff = now()->subMinutes(
            (int) config('reservations.duration_minutes', 120)
            + (int) config('reservations.buffer_minutes', 15)
        );

        // Borne du statut dérivé « expired » pour l'affichage : une demande
        // en attente qui dépasse le délai configuré apparaît comme expirée,
        // même si la commande planifiée ne l'a pas encore horodatée (le
        // changement de statut en base reste le travail de la commande).
        $expiredLimit = now()->subHours((int) config('reservations.pending_expiration_hours', 24));

        $query = Reservation::with(['user', 'table'])
            ->withCount(['user as absences_count' => function ($query) {
                // Le masquage ne modifie jamais le compteur : seules les
                // absences « excusées » en sont retirées.
                $query->where('attendance', Reservation::ATTENDANCE_ABSENT);
            }])
            ->when($onlyHidden, fn ($query) => $query->hidden(), fn ($query) => $query->visible())
            ->when($onlyExpired, function ($query) use ($expiredLimit) {
                $query->where(function ($visible) use ($expiredLimit) {
                    $visible->whereNotNull('expired_at')
                        ->orWhere(function ($late) use ($expiredLimit) {
                            $late->where('status', Reservation::STATUS_PENDING)
                                ->whereNull('expired_at')
                                ->where('created_at', '<=', $expiredLimit);
                        });
                });
            })
            ->when($onlyCompleted, function ($query) use ($completedCutoff) {
                // SQLite stocke la date au format datetime (« 2026-10-20
                // 00:00:00 ») : on tronque à la date avant de concaténer
                // l'heure. MySQL dispose de TIMESTAMP(date, time).
                $expression = $query->getConnection()->getDriverName() === 'sqlite'
                    ? "substr(reservation_date, 1, 10) || ' ' || reservation_time"
                    : 'TIMESTAMP(reservation_date, reservation_time)';

                $query->where('status', Reservation::STATUS_CONFIRMED)
                    ->whereNull('expired_at')
                    ->whereRaw($expression.' <= ?', [$completedCutoff->format('Y-m-d H:i:s')]);
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            });

        // Trie les résultats du plus récent au plus ancien (appliqué une
        // seule fois : le builder est réutilisé pour le re-paginage).
        $query->latest();

        $reservations = $query->paginate(10)
            ->withQueryString();

        // Un paramètre « page » devenu périmètre (une action vient de
        // retirer un élément de la liste, un filtre a réduit l'ensemble...)
        // ne doit pas donner l'impression que le filtre est cassé : on
        // revient à la dernière page réellement occupée.
        if ($reservations->isEmpty() && $reservations->currentPage() > 1) {
            $reservations = $query->paginate(10, ['*'], 'page', $reservations->lastPage())
                ->withQueryString();
        }

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

        // Une réservation confirmée dont le créneau est écoulé n'est plus
        // annulable : elle doit être traitée par la présence du client.
        if ($target === Reservation::STATUS_CANCELLED
            && $reservation->status === Reservation::STATUS_CONFIRMED
            && ! $reservation->canAdminCancel()) {
            return back()->with('error', 'Le créneau de cette réservation est terminé : elle ne peut plus être annulée. Enregistrez la présence du client (« Marquer présent » ou « Marquer absent »).');
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

        $previousStatus = $reservation->status;

        // Tout autre statut libère la table attribuée et la journée du client.
        $reservation->update([
            'status' => $target,
            'table_id' => null,
            'active_date' => null,
        ]);

        $reservation->loadMissing('user');

        // Le statut est enregistré : la notification part immédiatement.
        // Un échec d'envoi est journalisé sans annuler le changement.
        $recipient = $reservation->user?->email;

        if (is_string($recipient) && $recipient !== '') {
            try {
                if ($target === Reservation::STATUS_REJECTED && in_array($previousStatus, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
                    Mail::to($recipient)->send(new ReservationRejectedMail($reservation));
                } elseif ($target === Reservation::STATUS_CANCELLED && in_array($previousStatus, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
                    Mail::to($recipient)->send(new ReservationCancelledByAdminMail($reservation));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

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
            return back()->with('error', 'Seule une réservation confirmée dont le créneau est écoulé peut être marquée comme présence ou absence.');
        }

        $previousAttendance = $reservation->attendance;
        $target = $data['attendance'];

        // La présence est enregistrée UNE SEULE FOIS. Un rejeu identique
        // (double clic) est accepté sans rien changer ; en dehors de celui-
        // ci, la seule transition reste « absent → excusée ».
        if ($previousAttendance !== Reservation::ATTENDANCE_UNKNOWN && $target !== $previousAttendance) {
            $isExcuse = $previousAttendance === Reservation::ATTENDANCE_ABSENT
                && $target === Reservation::ATTENDANCE_EXCUSED;

            if (! $isExcuse) {
                if ($previousAttendance === Reservation::ATTENDANCE_ABSENT) {
                    return back()->with('error', 'L\'absence de ce client est déjà enregistrée : seule l\'action « Excuser l\'absence » reste disponible.');
                }

                return back()->with('error', 'La présence de ce client est déjà enregistrée et ne peut plus être modifiée.');
            }
        }

        $reservation->update(['attendance' => $target]);

        // Blocage automatique lorsque le client atteint le nombre
        // d'absences vérifiées autorisé.
        $blocked = $reservation->user?->applyAutomaticBookingBlock() ?? false;

        if ($data['attendance'] === Reservation::ATTENDANCE_ABSENT && $previousAttendance !== Reservation::ATTENDANCE_ABSENT) {
            $reservation->refresh();
            $reservation->loadMissing('user');
            if ($reservation->absence_warning_sent_at === null && $reservation->hidden_at === null && $reservation->countsAsAbsence()) {
                $user = $reservation->user;
                if ($user) {
                    // Réservation atomique du droit d'envoi : deux mises à
                    // jour simultanées ne peuvent pas envoyer deux e-mails.
                    $claimed = Reservation::query()
                        ->whereKey($reservation->getKey())
                        ->whereNull('absence_warning_sent_at')
                        ->update(['absence_warning_sent_at' => now()]);

                    if ($claimed) {
                        $absencesCount = $user->absencesCount();
                        $recipient = $user->email;
                        if (is_string($recipient) && $recipient !== '') {
                            try {
                                Mail::to($recipient)->send(new ReservationAbsenceWarningMail($user, $absencesCount, $reservation));
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }
                    }
                }
            }
        }

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
            return back()->with('error', 'Seule une réservation dont le créneau est écoulé peut être masquée — et sa présence doit d\'abord être enregistrée si elle était confirmée.');
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

                // Règle « une réservation par client et par jour ».
                //
                // La journée du client est consommée par une seule réservation
                // : `active_date` matérialise cette consommation. Si une autre
                // réservation du même client occupe déjà cette journée, la
                // confirmation est refusée — c'est le cas des doublons
                // historiques, qui restent intacts mais ne peuvent plus être
                // confirmés une seconde fois.
                $day = $reservation->reservation_date->toDateString();

                $dayAlreadyTaken = Reservation::query()
                    ->where('user_id', $reservation->user_id)
                    ->whereNotNull('active_date')
                    ->whereDate('active_date', $day)
                    ->whereKeyNot($reservation->getKey())
                    ->exists();

                if ($dayAlreadyTaken) {
                    return ['duplicate' => true, 'day' => $day];
                }

                // La réservation prend possession de la journée du client si
                // elle ne l'occupe pas déjà (doublon historique).
                //
                // L'index unique (user_id, active_date) reste la protection
                // finale : deux confirmations simultanées pour la même
                // journée ne peuvent pas aboutir toutes les deux.
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
        } catch (UniqueConstraintViolationException) {
            // Deux confirmations simultanées pour le même client et la même
            // journée : c'est l'index unique qui a arbitré. Ce n'est pas une
            // panne, c'est la règle métier : on l'explique à l'administrateur.
            return back()->with('error', $this->duplicateDayMessage($reservation->reservation_date));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Une erreur est survenue lors de la confirmation de la réservation.');
        }

        if ($result['unavailable'] ?? false) {
            return back()->with('error', "Aucune table disponible pour ce créneau. Vérifiez les capacités, l'état des tables ou libérez une table occupée.");
        }

        if ($result['duplicate'] ?? false) {
            return back()->with('error', $this->duplicateDayMessage($result['day'] ?? null));
        }

        if ($result['already'] ?? false) {
            return back()->with('success', 'La réservation est déjà confirmée.');
        }

        // La table vient d'être attribuée : on recharge la relation avant
        // de composer l'e-mail de confirmation.
        $reservation->loadMissing(['user', 'table']);
        $recipient = $reservation->user?->email;
        if (is_string($recipient) && $recipient !== '') {
            try {
                Mail::to($recipient)->send(new ReservationConfirmedMail($reservation));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', "Réservation confirmée. Table n°{$result['number']} attribuée.");
    }

    /**
     * Message d'erreur de la règle « une réservation par client et par jour ».
     */
    private function duplicateDayMessage(string|CarbonInterface|null $day): string
    {
        if ($day === null) {
            $date = 'cette journée';
        } else {
            $date = ($day instanceof CarbonInterface ? $day : Carbon::parse($day))
                ->translatedFormat('d/m/Y');
        }

        return 'Confirmation refusée : ce client a déjà une réservation active pour le '.$date.'. Un client ne peut avoir qu\'une réservation par jour.';
    }
}
