<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WalkIn extends Model
{
    protected $fillable = [
        'table_id',
        'guests_count',
        'special_request',
        'seated_at',
        'released_at',
    ];

    protected $casts = [
        'guests_count' => 'integer',
        'seated_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    /** Un client sans réservation occupe encore la table tant qu'elle n'est pas libérée. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('released_at');
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }

    /**
     * Fin de l'occupation : heure de libération si la session est fermée,
     * sinon la durée prévue. Le temps de préparation est ajouté après.
     */
    public function effectiveEnd(): CarbonInterface
    {
        $end = $this->released_at
            ?? $this->seated_at->copy()->addMinutes((int) config('reservations.duration_minutes', 120));

        return $end->copy()->addMinutes((int) config('reservations.buffer_minutes', 15));
    }

    /** Fin du service de la journée d'arrivée. */
    public function serviceEnd(): CarbonInterface
    {
        return Carbon::parse(
            $this->seated_at->toDateString().' '.(string) config('reservations.closing_time', '22:00')
        );
    }

    /**
     * Fin du blocage de la table.
     *
     * - session libérée : libération + temps de préparation ;
     * - session active : la table reste bloquée jusqu'à la fin du service
     *   de la journée d'arrivée. Le lendemain n'est jamais impacté.
     */
    public function blockingEnd(): CarbonInterface
    {
        if ($this->released_at !== null) {
            return $this->released_at->copy()->addMinutes((int) config('reservations.buffer_minutes', 15));
        }

        return $this->serviceEnd()->addMinutes((int) config('reservations.buffer_minutes', 15));
    }

    /**
     * Cette visite occupe-elle la table à l'instant demandé ?
     *
     * Tant que la session n'est pas fermée par l'administrateur, la table reste
     * occupée : on ne libère jamais automatiquement une table encore utilisée.
     */
    public function covers(CarbonInterface $now): bool
    {
        if ($now->lt($this->seated_at)) {
            return false;
        }

        if ($this->isActive()) {
            return true;
        }

        return $now->lt($this->effectiveEnd());
    }

    /** Session active dont la durée prévue est dépassée (oubli de fermeture). */
    public function isOverdue(CarbonInterface $now): bool
    {
        return $this->isActive() && $now->gt($this->effectiveEnd());
    }
}
