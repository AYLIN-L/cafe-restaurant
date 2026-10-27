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
        'absence_warning_sent_at',
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
        'attendance' => 'string',
        'absence_warning_sent_at' => 'datetime',
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

    /** Le créneau de la réservation est-il déjà commencé ? */
    public function isPast(): bool
    {
        return $this->scheduledAt()->isPast();
    }

    /**
     * Le créneau est-il terminé (durée + temps de préparation) ?
     *
     * C'est la borne unique du cycle de vie administrateur : tant que le
     * créneau court, la réservation reste annulable ; une fois écoulé, elle
     * passe en enregistrement de présence puis en masquage. Elle correspond
     * exactement au statut dérivé « completed ».
     */
    public function hasEnded(): bool
    {
        return $this->effectiveEnd()->isPast();
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

    /**
     * La réservation peut-elle être masquée ? (jamais supprimée)
     *
     * Deux conditions, dans l'ordre :
     * 1. le créneau doit être écoulé ;
     * 2. une réservation CONFIRMÉE doit avoir une présence enregistrée —
     *    on ne masque jamais un passage non traité.
     *
     * Une demande en attente, refusée ou annulée n'a aucune présence à
     * enregistrer : la fin du créneau suffit.
     */
    public function isHideable(): bool
    {
        if ($this->hidden_at !== null || ! $this->hasEnded()) {
            return false;
        }

        if ($this->status === self::STATUS_CONFIRMED) {
            return $this->attendance !== self::ATTENDANCE_UNKNOWN;
        }

        return true;
    }

    /**
     * L'administrateur peut-il encore annuler cette réservation ?
     *
     * Annulation autorisée uniquement tant que le créneau n'est pas écoulé.
     * (isCancellable() — réservé au client — garde sa propre borne : le
     * début du créneau.)
     */
    public function canAdminCancel(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true) && ! $this->hasEnded();
    }

    /** Libellé de la présence enregistrée. */
    public function attendanceLabel(): string
    {
        return (string) (config('reservations.attendance.'.$this->attendance.'.label') ?? 'Non renseignée');
    }

    /**
     * La présence peut-elle être enregistrée ?
     *
     * Seule une réservation confirmée DONT LE CRÉNEAU EST ÉCOULÉ peut être
     * marquée : une demande refusée, annulée ou expirée ne peut jamais être
     * assimilée à une absence, et on ne saisit pas la présence d'un service
     * qui n'a pas encore eu lieu.
     */
    public function canRecordAttendance(): bool
    {
        return $this->status === self::STATUS_CONFIRMED && $this->hasEnded();
    }

    /**
     * Cette réservation compte-t-elle comme une absence non excusée ?
     *
     * Seule la valeur « absent » compte : « excused » ne compte jamais.
     * Le masquage n'entre pas en jeu — masquer une réservation ne retire
     * jamais une absence du compteur.
     */
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

    /**
     * Réservations qui comptent comme activité.
     *
     * La règle métier fait foi : une réservation annulée, refusée ou expirée
     * ne représente aucun client. On s'appuie donc sur la constante
     * ACTIVE_STATUSES (et non sur une liste de statuts à exclure) afin que
     * les statistiques restent alignées sur la règle, quel que soit le
     * statut que l'application ajouterait plus tard.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Réservations planifiées entre deux journées (bornes incluses).
     *
     * Le filtre porte sur `reservation_date` — la date de la réservation —
     * et jamais sur `created_at` (date de création) : une statistique
     * « aujourd'hui » ou « cette semaine » décrit des rendez-vous, pas des
     * demandes.
     *
     * Les deux bornes sont comparées en mode DATE : la borne haute reste
     * incluse quel que soit le moteur (sur SQLite la valeur est stockée
     * avec une heure, un `between` sur la date seule l'exclurait).
     */
    public function scopeScheduledBetween(Builder $query, string|CarbonInterface $from, string|CarbonInterface $to): Builder
    {
        $fromDay = $from instanceof CarbonInterface ? $from->format('Y-m-d') : Carbon::parse($from)->format('Y-m-d');
        $toDay = $to instanceof CarbonInterface ? $to->format('Y-m-d') : Carbon::parse($to)->format('Y-m-d');

        return $query->whereDate('reservation_date', '>=', $fromDay)
            ->whereDate('reservation_date', '<=', $toDay);
    }
}
