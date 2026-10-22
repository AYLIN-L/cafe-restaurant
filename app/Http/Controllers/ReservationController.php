<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Services\TableAssignmentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(private TableAssignmentService $assignment) {}

    // Affiche l'historique des réservations du client connecté.
    public function index()
    {
        $user = auth()->user();

        $reservations = $user->reservations()
            ->with('table')
            ->latest()
            ->paginate(10);

        return view('reservations.index', [
            'reservations' => $reservations,
            'bookingBlock' => $user->bookingBlockState(),
        ]);
    }

    // Affiche le formulaire de réservation.
    public function create()
    {
        $user = auth()->user();
        $defaultGuests = 2;
        $slots = $this->assignment->availableSlots(now()->toDateString(), $defaultGuests);

        return view('reservations.create', [
            'minDate' => now()->toDateString(),
            'maxDate' => $this->maxDate(),
            'slotTimes' => $this->assignment->slotTimes(),
            'openingTime' => $this->assignment->openingTime(),
            'closingTime' => $this->assignment->closingTime(),
            'durationMinutes' => $this->assignment->duration(),
            'bufferMinutes' => $this->assignment->buffer(),
            'maxGuests' => Table::maxActiveCapacity(),
            'availabilityUrl' => route('reservations.availability'),
            'initialSlots' => $slots,
            'defaultDate' => now()->toDateString(),
            'defaultGuests' => $defaultGuests,
            'defaultTime' => collect($slots)->firstWhere('available', true)['time'] ?? '',
            'bookingBlock' => $user->bookingBlockState(),
        ]);
    }

    /**
     * Vérifie la disponibilité d'un créneau (appelé en AJAX).
     * Ne renvoie jamais d'identifiant de table ni d'information interne.
     */
    public function availability(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'reservation_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$this->maxDate()],
                'reservation_time' => ['required', 'date_format:H:i'],
                'guests_count' => ['required', 'integer', 'min:1', 'max:20'],
            ], [
                'reservation_date.required' => 'Veuillez sélectionner une date.',
                'reservation_date.after_or_equal' => 'La date de réservation ne peut pas être dans le passé.',
                'reservation_date.before_or_equal' => 'Vous pouvez réserver au maximum '.config('reservations.booking_window_days', 7).' jours à l\'avance.',
                'reservation_time.required' => 'Veuillez sélectionner une heure.',
                'reservation_time.date_format' => 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.',
                'guests_count.required' => 'Veuillez indiquer le nombre de convives.',
                'guests_count.max' => 'Le nombre de convives demandé ne correspond à aucune table du restaurant.',
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'available' => false,
                'message' => $exception->validator->errors()->first(),
                'reason' => 'invalid',
                'can_book' => true,
                'duration_minutes' => $this->assignment->duration(),
                'end_time' => null,
                'slots' => [],
            ], 422);
        }

        $user = auth()->user();
        $date = $data['reservation_date'];
        $time = $data['reservation_time'];
        $guests = (int) $data['guests_count'];

        $payload = [
            'duration_minutes' => $this->assignment->duration(),
            'end_time' => null,
            'slots' => $this->assignment->availableSlots($date, $guests),
            'can_book' => true,
            'reason' => null,
        ];

        // Compte bloqué : plus aucune réservation possible.
        if ($user->isBookingBlocked()) {
            return $this->availabilityResponse($payload, false, $this->blockedMessage(), 'blocked', false, 200);
        }

        if (! $this->assignment->isWithinHours($time) || ! $this->assignment->isSlotStep($time)) {
            return $this->availabilityResponse($payload, false, 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.', 'invalid', true, 422);
        }

        if ($guests > Table::maxActiveCapacity() || ! $this->assignment->canAccommodate($guests)) {
            return $this->availabilityResponse($payload, false, 'Aucune table adaptée n\'est disponible à cette heure.', 'no_capacity', true, 422);
        }

        // Le créneau est-il déjà écoulé ?
        if ($this->assignment->startAt($date, $time)->isPast()) {
            return $this->availabilityResponse($payload, false, 'Ce créneau est déjà passé. Veuillez choisir un horaire à venir.', 'past', true, 200);
        }

        // Une seule réservation par client et par jour.
        if ($this->hasReservationOnDate($user, $date)) {
            return $this->availabilityResponse($payload, false, 'Vous avez déjà une réservation pour cette date : une seule réservation est possible par jour et par personne.', 'day_taken', true, 200);
        }

        $available = $this->assignment->isSlotAvailable($date, $time, $guests);

        return $this->availabilityResponse(
            $payload,
            $available,
            $available
                ? 'Des tables sont disponibles pour ce créneau.'
                : 'Aucune table adaptée n\'est disponible à cette heure.',
            $available ? null : 'no_table',
            true,
            200,
            $available
                ? $this->assignment->effectiveEnd($this->assignment->startAt($date, $time))->format('H:i')
                : null
        );
    }

    // Valide et enregistre une réservation.
    public function store(Request $request)
    {
        $data = $request->validate([
            'reservation_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$this->maxDate()],
            'reservation_time' => [
                'required',
                'date_format:H:i',
                Rule::in($this->assignment->slotTimes()),
            ],
            'guests_count' => ['required', 'integer', 'min:1', 'max:20'],
            'special_request' => ['nullable', 'string', 'max:500'],
        ], [
            'reservation_date.before_or_equal' => 'Vous pouvez réserver au maximum '.config('reservations.booking_window_days', 7).' jours à l\'avance.',
            'reservation_date.after_or_equal' => 'La date de réservation ne peut pas être dans le passé.',
            'reservation_time.in' => 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.',
            'reservation_time.date_format' => 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.',
            'guests_count.max' => 'Le nombre de convives demandé ne correspond à aucune table du restaurant.',
        ]);

        $user = auth()->user();
        $date = $data['reservation_date'];
        $time = $data['reservation_time'];
        $guests = (int) $data['guests_count'];

        // Un client bloqué (absences vérifiées) ne peut plus réserver.
        if ($user->isBookingBlocked()) {
            throw ValidationException::withMessages([
                'reservation_date' => $this->blockedMessage(),
            ]);
        }

        // Un créneau déjà écoulé ne peut pas être réservé.
        if ($this->assignment->startAt($date, $time)->isPast()) {
            throw ValidationException::withMessages([
                'reservation_time' => 'Ce créneau est déjà passé. Veuillez choisir un horaire à venir.',
            ]);
        }

        if (! $this->assignment->isWithinHours($time)) {
            throw ValidationException::withMessages([
                'reservation_time' => 'Les réservations sont possibles entre '.$this->assignment->openingTime().' et '.$this->assignment->closingTime().'.',
            ]);
        }

        // Aucune table ne pourrait accueillir la demande : inutile d'enregistrer.
        if ($guests > Table::maxActiveCapacity() || ! $this->assignment->canAccommodate($guests)) {
            throw ValidationException::withMessages([
                'guests_count' => 'Le nombre de convives demandé ne correspond à aucune table du restaurant.',
            ]);
        }

        try {
            $reservation = DB::transaction(function () use ($user, $data, $date, $time, $guests) {
                // Verrou sur le client : deux demandes simultanées pour la
                // même journée ne peuvent pas être acceptées toutes les deux.
                User::whereKey($user->getKey())->lockForUpdate()->first();

                if ($this->hasReservationOnDate($user, $date)) {
                    throw ValidationException::withMessages([
                        'reservation_date' => $this->dayTakenMessage(),
                    ]);
                }

                // Recalcul serveur obligatoire : l'interface peut être
                // périmée. La table est recherchée pour vérifier que la
                // demande reste réalisable, mais elle n'est PAS attribuée :
                // une demande en attente ne bloque aucune table.
                $table = $this->assignment->findTableForSlot($date, $time, $guests, null, true);

                if ($table === null) {
                    throw ValidationException::withMessages([
                        'reservation_time' => 'Aucune table adaptée n\'est disponible à cette heure. Veuillez choisir un autre créneau.',
                    ]);
                }

                return $user->reservations()->create([
                    'reservation_date' => $date,
                    'reservation_time' => $time,
                    'guests_count' => $guests,
                    'special_request' => $data['special_request'] ?? null,
                    'status' => Reservation::STATUS_PENDING,
                    'active_date' => $date,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            // L'index unique (user_id, active_date) a bloqué une double
            // réservation pour la même journée.
            throw ValidationException::withMessages([
                'reservation_date' => $this->dayTakenMessage(),
            ]);
        }

        return redirect()->route('reservations.index')
            ->with('success', 'Votre réservation a bien été enregistrée. Elle est en attente de confirmation par notre équipe.');
    }

    // Annule une réservation appartenant au client connecté.
    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }

        if ($reservation->status === 'cancelled') {
            return back()->with('success', 'Cette réservation était déjà annulée.');
        }

        if (! in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        // Une réservation passée n'est plus annulable : elle appartient à
        // l'historique et reste quantifiable (présence, absence).
        if ($reservation->isPast()) {
            return back()->with('error', 'Cette réservation est passée : elle ne peut plus être annulée.');
        }

        $reservation->update([
            'status' => Reservation::STATUS_CANCELLED,
            'table_id' => null,
            // La journée est de nouveau disponible pour ce client.
            'active_date' => null,
        ]);

        return back()->with('success', 'Votre réservation a été annulée et le créneau est de nouveau disponible.');
    }

    /** Le client a-t-il déjà une réservation pour cette journée ? */
    protected function hasReservationOnDate(User $user, string $date): bool
    {
        return $user->reservations()
            ->whereNotNull('active_date')
            ->whereDate('active_date', $date)
            ->exists();
    }

    /** Réponse normalisée de l'endpoint de disponibilité. */
    protected function availabilityResponse(array $payload, bool $available, string $message, ?string $reason, bool $canBook = true, int $status = 200, ?string $endTime = null): JsonResponse
    {
        return response()->json(array_merge($payload, [
            'available' => $available,
            'message' => $message,
            'reason' => $reason,
            'can_book' => $canBook,
            'end_time' => $endTime,
        ]), $status);
    }

    /** Message public affiché au client bloqué. */
    protected function blockedMessage(): string
    {
        return 'Vos nouvelles réservations sont temporairement suspendues après plusieurs absences non justifiées. '
            .'Contactez le restaurant pour régulariser votre situation.';
    }

    /** Message affiché au client qui a déjà réservé ce jour-là. */
    protected function dayTakenMessage(): string
    {
        return 'Vous avez déjà une réservation pour cette date : une seule réservation est possible par jour et par personne.';
    }

    /** Date la plus éloignée pour une réservation. */
    protected function maxDate(): string
    {
        return now()->addDays((int) config('reservations.booking_window_days', 7))->toDateString();
    }
}
