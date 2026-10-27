<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
  protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'booking_blocked_at',
        'booking_block_override',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function contactMessages()
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function isAdmin():bool
    {
        return $this->role === 'admin';
    }

    /**
     * Nombre d'absences non excusées du client.
     *
     * Le compteur est toujours DÉRIVÉ des réservations : il ne peut donc
     * jamais être incrémenté deux fois pour une même réservation.
     *
     * MASQUER UNE RÉSERVATION NE MODIFIE JAMAIS CE COMPTEUR : seules les
     * absences « excusées » en sortent. L'historique masqué reste donc
     * compté, ce qui garde le seuil de blocage cohérent avec les absences
     * réellement non excusées.
     */
    public function absencesCount(): int
    {
        return $this->reservations()
            ->where('attendance', Reservation::ATTENDANCE_ABSENT)
            ->count();
    }

    /** Nombre d'absences vérifiées autorisé avant blocage. */
    public function absenceThreshold(): int
    {
        return (int) config('reservations.absence_threshold', 5);
    }

    /** Le client a-t-il atteint le seuil d'absences vérifiées ? */
    public function hasReachedAbsenceThreshold(): bool
    {
        return $this->absencesCount() >= $this->absenceThreshold();
    }

    /** Le client est-il bloqué pour ses nouvelles réservations ? */
    public function isBookingBlocked(): bool
    {
        return $this->booking_blocked_at !== null;
    }

    /** Le client a-t-il été explicitement exempté du seuil automatique ? */
    public function hasBookingBlockOverride(): bool
    {
        return (bool) $this->booking_block_override;
    }

    /** État complet du blocage (affichage administrateur). */
    public function bookingBlockState(): array
    {
        $absences = $this->absencesCount();
        $threshold = $this->absenceThreshold();

        return [
            'absences' => $absences,
            'threshold' => $threshold,
            'remaining' => max(0, $threshold - $absences),
            'blocked' => $this->isBookingBlocked(),
            'overridden' => $this->hasBookingBlockOverride(),
            'threshold_reached' => $absences >= $threshold,
        ];
    }

    /**
     * Applique le blocage automatique lorsque le seuil d'absences est
     * atteint. Un client explicitement exempté n'est jamais rebloqué
     * automatiquement.
     *
     * @return bool true si le blocage vient d'être posé.
     */
    public function applyAutomaticBookingBlock(): bool
    {
        if ($this->hasBookingBlockOverride() || $this->isBookingBlocked()) {
            return false;
        }

        if (! $this->hasReachedAbsenceThreshold()) {
            return false;
        }

        $this->forceFill(['booking_blocked_at' => now()])->save();

        return true;
    }

    /** Blocage posé manuellement par un administrateur. */
    public function blockBooking(): void
    {
        $this->forceFill([
            'booking_blocked_at' => now(),
            'booking_block_override' => false,
        ])->save();
    }

    /**
     * Déblocage par un administrateur.
     *
     * Le client est marqué comme exempté : le seuil automatique ne le
     * rebloquera pas à la prochaine absence.
     */
    public function unblockBooking(): void
    {
        $this->forceFill([
            'booking_blocked_at' => null,
            'booking_block_override' => true,
        ])->save();
    }
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'booking_blocked_at' => 'datetime',
            'booking_block_override' => 'boolean',
        ];
    }
}
