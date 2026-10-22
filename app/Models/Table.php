<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    /** États calculés d'une table (jamais stockés en base). */
    public const STATE_INACTIVE = 'inactive';

    public const STATE_WALK_IN = 'walk_in';

    public const STATE_OCCUPIED = 'occupied';

    public const STATE_RESERVED = 'reserved';

    public const STATE_FREE = 'free';

    protected $fillable = [
        'number',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'number' => 'integer',
        'capacity' => 'integer',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function walkIns()
    {
        return $this->hasMany(WalkIn::class);
    }

    /** Clients sans réservation actuellement installés. */
    public function activeWalkIns()
    {
        return $this->walkIns()->active();
    }

    /** Plus grande capacité parmi les tables actuellement en service. */
    public static function maxActiveCapacity(): int
    {
        return (int) static::query()->where('is_active', true)->max('capacity');
    }

    /**
     * État affichable d'une table à un instant donné.
     *
     * Le calcul se base sur les collections déjà chargées ($this->reservations
     * et $this->walkIns). Pour éviter les requêtes en cascade, les contrôleurs
     * doivent les précharger avec les contraintes du jour.
     */
    public function stateAt(CarbonInterface $now): string
    {
        if (! $this->is_active) {
            return self::STATE_INACTIVE;
        }

        // Un client sans réservation installé occupe physiquement la table.
        if ($this->walkIns->contains(fn (WalkIn $walkIn) => $walkIn->covers($now))) {
            return self::STATE_WALK_IN;
        }

        // Une réservation confirmée en cours (début <= maintenant < fin effective).
        if ($this->reservations->contains(fn (Reservation $reservation) => $this->coversInstant($reservation, $now))) {
            return self::STATE_OCCUPIED;
        }

        // Sinon, indique si la table est déjà réservée plus tard dans la journée.
        if ($this->reservations->contains(fn (Reservation $reservation) => $reservation->scheduledAt()->gt($now))) {
            return self::STATE_RESERVED;
        }

        return self::STATE_FREE;
    }

    /** Prochaine réservation confirmée chargée, si elle existe. */
    public function nextReservationAt(CarbonInterface $now): ?Reservation
    {
        return $this->reservations
            ->filter(fn (Reservation $reservation) => $reservation->scheduledAt()->gt($now))
            ->sort(fn (Reservation $a, Reservation $b) => $a->scheduledAt() <=> $b->scheduledAt())
            ->first();
    }

    /** Réservation en cours sur la table, si elle existe. */
    public function currentReservationAt(CarbonInterface $now): ?Reservation
    {
        return $this->reservations->first(fn (Reservation $reservation) => $this->coversInstant($reservation, $now));
    }

    /** Le créneau d'une réservation contient-il l'instant demandé ? */
    protected function coversInstant(Reservation $reservation, CarbonInterface $now): bool
    {
        return $reservation->scheduledAt()->lte($now) && $reservation->effectiveEnd()->gt($now);
    }
}
