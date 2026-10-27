<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public ?string $rejectionReason = null)
    {
        $this->reservation->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mise à jour concernant votre demande de réservation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.rejected',
            text: 'emails.reservations.rejected_text',
            with: [
                'reservation' => $this->reservation,
                'user' => $this->reservation->user,
                'rejectionReason' => $this->rejectionReason,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
