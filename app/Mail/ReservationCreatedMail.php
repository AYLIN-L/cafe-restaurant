<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nous avons bien reçu votre demande de réservation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.created',
            text: 'emails.reservations.created_text',
            with: [
                'reservation' => $this->reservation,
                'user' => $this->reservation->user,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
