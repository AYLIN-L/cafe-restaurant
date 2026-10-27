<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing(['user', 'table']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre réservation est confirmée !',
        );
    }

    public function content(): Content
    {
        $tableNumber = $this->reservation->table?->number;
        if ($tableNumber === null && $this->reservation->table_id) {
            $tableNumber = Table::find($this->reservation->table_id)?->number;
        }

        $openingTime = config('reservations.opening_time');
        $closingTime = config('reservations.closing_time');

        return new Content(
            view: 'emails.reservations.confirmed',
            text: 'emails.reservations.confirmed_text',
            with: [
                'reservation' => $this->reservation,
                'user' => $this->reservation->user,
                'tableNumber' => $tableNumber,
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
