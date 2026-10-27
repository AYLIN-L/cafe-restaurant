<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCancelledByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public ?string $cancellationReason = null)
    {
        $this->reservation->loadMissing(['user', 'table']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Information importante concernant votre réservation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.cancelled_by_admin',
            text: 'emails.reservations.cancelled_by_admin_text',
            with: [
                'reservation' => $this->reservation,
                'user' => $this->reservation->user,
                'cancellationReason' => $this->cancellationReason,
                'tableNumber' => $this->reservation->table?->number,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
