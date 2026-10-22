<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Table;
use App\Models\WalkIn;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur unique de disponibilité des tables.
 *
 * Toutes les règles (horaires, créneaux, durée, temps de préparation,
 * chevauchement) vivent ici. Le formulaire client, l'endpoint AJAX, la
 * validation serveur, l'attribution admin et les clients sans réservation
 * passent tous par cette classe : une seule logique, jamais deux.
 */
class TableAssignmentService
{
    /** Durée d'une réservation, en minutes. */
    public function duration(): int
    {
        return (int) config('reservations.duration_minutes', 120);
    }

    /** Temps de préparation ajouté après chaque réservation, en minutes. */
    public function buffer(): int
    {
        return (int) config('reservations.buffer_minutes', 15);
    }

    public function openingTime(): string
    {
        return (string) config('reservations.opening_time', '08:00');
    }

    public function closingTime(): string
    {
        return (string) config('reservations.closing_time', '22:00');
    }

    public function slotStep(): int
    {
        return (int) config('reservations.slot_step_minutes', 30);
    }

    /** Dernière heure de début possible : fermeture - durée. */
    public function lastStartTime(): string
    {
        return $this->minutesToTime($this->minutesOfDay($this->closingTime()) - $this->duration());
    }

    /** Toutes les heures de début proposées : 08:00, 08:30, ... jusqu'à 20:00. */
    public function slotTimes(): array
    {
        $slots = [];

        for ($minutes = $this->minutesOfDay($this->openingTime()); $minutes <= $this->minutesOfDay($this->lastStartTime()); $minutes += $this->slotStep()) {
            $slots[] = $this->minutesToTime($minutes);
        }

        return $slots;
    }

    /** Fin du blocage d'une table : début + durée + temps de préparation. */
    public function effectiveEnd(CarbonInterface $start): CarbonInterface
    {
        return $start->copy()->addMinutes($this->duration())->addMinutes($this->buffer());
    }

    /** L'heure proposée respecte-t-elle les horaires d'ouverture ? */
    public function isWithinHours(string $time): bool
    {
        $minutes = $this->minutesOfDay($time);

        return $minutes >= $this->minutesOfDay($this->openingTime())
            && $minutes + $this->duration() <= $this->minutesOfDay($this->closingTime());
    }

    /** L'heure proposée correspond-elle au pas de réservation (30 min) ? */
    public function isSlotStep(string $time): bool
    {
        return $this->minutesOfDay($time) % $this->slotStep() === 0;
    }

    /** Date/heure de début valide pour une date et une heure données. */
    public function startAt(string|CarbonInterface $date, string $time): CarbonInterface
    {
        // Le cast 'date' du modèle renvoie déjà un objet Carbon : concaténer
        // une heure dessus produirait « 2026-10-20 00:00:00 12:30 ».
        if ($date instanceof CarbonInterface) {
            return $date->copy()->setTimeFromTimeString($time);
        }

        return Carbon::parse($date.' '.$time);
    }

    /**
     * Une table active peut-elle accueillir ce nombre de convives ?
     *
     * Contrôle de CAPACITÉ uniquement : il ne dit rien de la disponibilité
     * horaire. Il sert à refuser une demande dont aucune table ne pourra
     * jamais être attribuée, même plus tard.
     */
    public function canAccommodate(int $guests): bool
    {
        return Table::query()
            ->where('is_active', true)
            ->where('capacity', '>=', $guests)
            ->exists();
    }

    /**
     * Table la plus petite, active et libre pour un créneau donné.
     * À appeler dans une transaction (verrouillage) pour l'attribution.
     */
    public function findTableForSlot(string|CarbonInterface $date, string $time, int $guests, ?int $ignoreReservationId = null, bool $lock = false): ?Table
    {
        $start = $this->startAt($date, $time);

        return $this->findTableForInterval($start, $guests, $ignoreReservationId, $lock);
    }

    /** Variante à partir d'un objet date/heure (utilisée pour les clients sans réservation). */
    public function findTableForInterval(CarbonInterface $start, int $guests, ?int $ignoreReservationId = null, bool $lock = false): ?Table
    {
        $end = $this->effectiveEnd($start);

        $tables = $this->candidateTables($guests, $lock);

        if ($tables->isEmpty()) {
            return null;
        }

        $blocked = $this->blockedIntervals($tables, $start, $end, $ignoreReservationId);

        return $tables->first(function (Table $table) use ($blocked, $start, $end) {
            return ! $this->hasConflict($blocked[$table->id] ?? [], $start, $end);
        });
    }

    /** Ce créneau possède-t-il au moins une table adaptée ? */
    public function isSlotAvailable(string $date, string $time, int $guests, ?int $ignoreReservationId = null): bool
    {
        return $this->findTableForSlot($date, $time, $guests, $ignoreReservationId) !== null;
    }

    /**
     * Liste des créneaux de la journée avec leur disponibilité.
     * Une seule requête par table : pas de requête en cascade.
     */
    public function availableSlots(string $date, int $guests, ?int $ignoreReservationId = null): array
    {
        $tables = $this->candidateTables($guests);

        // Bornes larges : couvre les réservations qui passent minuit.
        $windowStart = Carbon::parse($date.' '.$this->openingTime());
        $windowEnd = Carbon::parse($date.' '.$this->closingTime());

        $blocked = $tables->isEmpty()
            ? []
            : $this->blockedIntervals($tables, $windowStart->copy()->subDay(), $windowEnd->copy()->addDay(), $ignoreReservationId);

        $now = now();

        $slots = [];

        foreach ($this->slotTimes() as $time) {
            $start = $this->startAt($date, $time);

            // Les créneaux déjà passés sont proposés mais indisponibles.
            $available = ! $start->lt($now)
                && $this->hasUsableTable($tables, $blocked, $start);

            $slots[] = [
                'time' => $time,
                'available' => $available,
            ];
        }

        return $slots;
    }

    /**
     * Installe un client sans réservation. La table n'est libérée que lorsque
     * l'administrateur termine explicitement la session.
     *
     * L'attribution doit être appelée dans une transaction : le verrou sur
     * les tables candidates évite que deux clients simultanés ne se
     * retrouvent sur la même table.
     */
    public function seatWalkIn(int $guests, ?string $specialRequest = null, ?CarbonInterface $start = null, bool $lock = false): ?WalkIn
    {
        $start ??= now();

        $table = $this->findTableForInterval($start, $guests, null, $lock);

        if ($table === null) {
            return null;
        }

        return WalkIn::create([
            'table_id' => $table->id,
            'guests_count' => $guests,
            'special_request' => $specialRequest,
            'seated_at' => $start,
        ]);
    }

    /** Tables actives ayant assez de places, de la plus petite à la plus grande. */
    protected function candidateTables(int $guests, bool $lock = false): Collection
    {
        $query = Table::query()
            ->where('is_active', true)
            ->where('capacity', '>=', $guests)
            ->orderBy('capacity')
            ->orderBy('number');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * Regroupe par table les intervalles bloqués susceptibles de recouvrir
     * la fenêtre.
     *
     * Seules les réservations CONFIRMÉES bloquent une table : une demande
     * en attente ne réserve rien (elle est attribuée à la confirmation).
     * Les clients sans réservation bloquent leur table jusqu'à leur
     * libération explicite, puis pendant le temps de préparation.
     *
     * @return array<int, array<int, array{start: CarbonInterface, end: CarbonInterface}>>
     */
    protected function blockedIntervals(Collection $tables, CarbonInterface $from, CarbonInterface $until, ?int $ignoreReservationId): array
    {
        $tableIds = $tables->pluck('id');

        $blocked = [];

        Reservation::query()
            ->whereIn('table_id', $tableIds)
            ->confirmed()
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            // whereDate : la colonne peut être stockée en datetime, et la
            // comparaison de chaînes « 2026-10-21 00:00:00 » <= « 2026-10-21 »
            // échouerait sur SQLite.
            ->whereDate('reservation_date', '>=', $from->toDateString())
            ->whereDate('reservation_date', '<=', $until->toDateString())
            ->get()
            ->each(function (Reservation $reservation) use (&$blocked) {
                $blocked[$reservation->table_id][] = [
                    'start' => $reservation->scheduledAt(),
                    'end' => $reservation->effectiveEnd(),
                ];
            });

        $buffer = $this->buffer();

        WalkIn::query()
            ->whereIn('table_id', $tableIds)
            ->where('seated_at', '<', $until)
            ->where(function ($query) use ($from, $buffer) {
                // Session active : la table reste bloquée jusqu'à la fin du
                // service de la journée d'arrivée (jamais jusqu'au lendemain).
                $query->whereNull('released_at')
                    // Session libérée : blocage jusqu'à la libération + préparation.
                    ->orWhere('released_at', '>', $from->copy()->subMinutes($buffer));
            })
            ->get()
            ->each(function (WalkIn $walkIn) use (&$blocked) {
                $blocked[$walkIn->table_id][] = [
                    'start' => $walkIn->seated_at,
                    'end' => $walkIn->blockingEnd(),
                ];
            });

        return $blocked;
    }

    /** Existe-t-il une table non bloquée dans l'intervalle demandé ? */
    protected function hasUsableTable(Collection $tables, array $blocked, CarbonInterface $start): bool
    {
        $end = $this->effectiveEnd($start);

        return $tables->contains(function (Table $table) use ($blocked, $start, $end) {
            return ! $this->hasConflict($blocked[$table->id] ?? [], $start, $end);
        });
    }

    /**
     * Règle de chevauchement : deux intervalles [s1, e1) et [s2, e2) se
     * chevauchent si et seulement si s1 < e2 ET s2 < e1.
     */
    protected function hasConflict(array $intervals, CarbonInterface $start, CarbonInterface $end): bool
    {
        foreach ($intervals as $interval) {
            if ($start->lt($interval['end']) && $interval['start']->lt($end)) {
                return true;
            }
        }

        return false;
    }

    /** Minutes depuis minuit pour une heure au format H:i(:s). */
    protected function minutesOfDay(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    /** Minutes depuis minuit → heure au format H:i. */
    protected function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
