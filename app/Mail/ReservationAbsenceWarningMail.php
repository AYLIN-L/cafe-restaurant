<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationAbsenceWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $absencesCount, public ?Reservation $reservation = null)
    {
        $this->user->loadMissing('reservations');
    }

    public function envelope(): Envelope
    {
        $threshold = (int) config('reservations.absence_threshold', 5);

        if ($this->absencesCount >= $threshold) {
            $subject = 'Restriction de réservation appliquée';
        } elseif ($this->absencesCount === 4) {
            $subject = 'Avertissement : dernier avertissement avant restriction';
        } elseif ($this->absencesCount === 3) {
            $subject = 'Avertissement important concernant vos absences';
        } elseif ($this->absencesCount === 2) {
            $subject = 'Deuxième avertissement pour absence non justifiée';
        } else {
            $subject = 'Absence enregistrée - Rappel du règlement';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $threshold = (int) config('reservations.absence_threshold', 5);
        $openingTime = config('reservations.opening_time');
        $closingTime = config('reservations.closing_time');

        return new Content(
            view: 'emails.reservations.absence_warning',
            text: 'emails.reservations.absence_warning_text',
            with: [
                'user' => $this->user,
                'absencesCount' => $this->absencesCount,
                'threshold' => $threshold,
                'reservation' => $this->reservation,
                'openingTime' => $openingTime,
                'closingTime' => $closingTime,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
