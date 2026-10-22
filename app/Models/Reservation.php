<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /** Toutes les valeurs autorisées pour le statut d'une réservation. */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Statuts qui immobilisent la journée du client.
     *
     * Une réservation annulée, refusée ou expirée libère la journée : le
     * client peut alors réserver à nouveau pour cette date.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
    ];

    /** Présence non renseignée (valeur par défaut). */
    public const ATTENDANCE_UNKNOWN = 'unknown';

    /** Le client s'est présenté. */
    public const ATTENDANCE_PRESENT = 'present';

    /** Le client ne s'est pas présenté : absence vérifiée. */
    public const ATTENDANCE_ABSENT = 'absent';

    /** Le client a été excusé : absence vérifiée mais non pénalisée. */
    public const ATTENDANCE_EXCUSED = 'excused';

    /** Toutes les présences possibles. */
    public const ATTENDANCES = [
        self::ATTENDANCE_UNKNOWN,
        self::ATTENDANCE_PRESENT,
        self::ATTENDANCE_ABSENT,
        self::ATTENDANCE_EXCUSED,
    ];

    protected $fillable = [
        'user_id',
        'table_id',
        'reservation_date',
        'reservation_time',
        'guests_count',
        'special_request',
        'status',
        'active_date',
        'attendance',
        'expired_at',
        'hidden_at',
        'hidden_by',
    ];

    protected $attributes = [
        'attendance' => self::ATTENDANCE_UNKNOWN,
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'active_date' => 'date',
        'guests_count' => 'integer',
        'expired_at' => 'datetime',
        'hidden_at' => 'datetime',
    ];

    protected $table = 'reservations';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    /** Administrateur ayant masqué la réservation. */
    public function hider()
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /** Date et heure de début réunies en un seul objet date/heure. */
    public function scheduledAt(): CarbonInterface
    {
        return $this->reservation_date->copy()
            ->setTimeFromTimeString((string) $this->reservation_time);
    }

    /** Fin prévue de la réservation (sans le temps de préparation). */
    public function expectedEnd(): CarbonInterface
    {
        return $this->scheduledAt()->copy()
            ->addMinutes((int) config('reservations.duration_minutes', 120));
    }

    /** Fin du blocage de la table (réservation + temps de préparation). */
    public function effectiveEnd(): CarbonInterface
    {
        return $this->expectedEnd()->copy()
            ->addMinutes((int) config('reservations.buffer_minutes', 15));
    }

    /**
     * Statut affiché.
     *
     * « completed » et « expired » n'existent pas en base : ils sont dérivés
     * (temps écoulé pour l'un, horodatage d'expiration pour l'autre).
     */
    public function displayStatus(): string
    {
        if ($this->expired_at !== null) {
            return 'expired';
        }

        if ($this->status === self::STATUS_CONFIRMED && $this->effectiveEnd()->isPast()) {
            return 'completed';
        }

        return $this->status;
    }

    /** La réservation a-t-elle été terminée ? (statut final) */
    public function isFinished(): bool
    {
        return $this->displayStatus() === 'completed';
    }

    /** Le créneau de la réservation est-il déjà écoulé ? */
    public function isPast(): bool
    {
        return $this->scheduledAt()->isPast();
    }

    /** La réservation immobilise-t-elle la journée du client ? */
    public function consumesDay(): bool
    {
        return $this->active_date !== null;
    }

    /** La réservation est-elle encore active (à venir ou en cours) ? */
    public function isActiveBooking(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true) && ! $this->isPast();
    }

    /** Le client peut-il encore annuler cette réservation ? */
    public function isCancellable(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true) && ! $this->isPast();
    }

    /** La réservation peut-elle être masquée ? (jamais supprimée) */
    public function isHideable(): bool
    {
        return $this->hidden_at === null && ! $this->isActiveBooking();
    }

    /** Libellé de la présence enregistrée. */
    public function attendanceLabel(): string
    {
        return (string) (config('reservations.attendance.'.$this->attendance.'.label') ?? 'Non renseignée');
    }

    /**
     * La présence peut-elle être enregistrée ?
     *
     * Seule une réservation confirmée peut être marquée comme honorée ou
     * absente : une demande refusée, annulée ou expirée ne peut jamais
     * être assimilée à une absence.
     */
    public function canRecordAttendance(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /** Cette réservation compte-t-elle comme une absence vérifiée ? */
    public function countsAsAbsence(): bool
    {
        return $this->attendance === self::ATTENDANCE_ABSENT;
    }

    /**
     * Tente de prendre possession de la journée du client.
     *
     * La journée est consommée par la PREMIÈRE réservation du client pour
     * cette date. Une réservation plus récente (doublon historique) ne
     * prend donc pas la place : elle reste sans date active.
     *
     * @return bool true si la réservation possède désormais la journée.
     */
    public function claimActiveDate(): bool
    {
        if ($this->active_date !== null) {
            return true;
        }

        $day = $this->reservation_date->toDateString();

        $alreadyClaimed = static::query()
            ->where('user_id', $this->user_id)
            ->whereNotNull('active_date')
            ->whereDate('active_date', $day)
            ->whereKeyNot($this->getKey())
            ->exists();

        if ($alreadyClaimed) {
            return false;
        }

        $this->forceFill(['active_date' => $day])->save();

        return true;
    }

    /** Réservations confirmées : seules celles-ci bloquent une table. */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /** Réservations visibles : les réservations masquées sont exclues. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    /** Réservations masquées. */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->whereNotNull('hidden_at');
    }

    /** Réservations qui immobilisent la journée du client. */
    public function scopeDayConsuming(Builder $query): Builder
    {
        return $query->whereNotNull('active_date');
    }

    /** Réservations d'une journée. */
    public function scopeForDate(Builder $query, string|CarbonInterface $date): Builder
    {
        $day = $date instanceof CarbonInterface
            ? $date->format('Y-m-d')
            : Carbon::parse($date)->format('Y-m-d');

        return $query->whereDate('reservation_date', $day);
    }
}
