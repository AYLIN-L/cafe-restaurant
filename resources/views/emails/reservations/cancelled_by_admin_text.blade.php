Information importante concernant votre réservation

Bonjour {{ $user->name }},

Nous vous informons avec nos excuses que votre réservation a été annulée par l'administration du restaurant.

Détails de la réservation :
- Date : {{ $reservation->reservation_date->format('d/m/Y') }}
- Heure : {{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}
- Nombre de convives : {{ $reservation->guests_count }}
@if($tableNumber)- Table attribuée : Table n° {{ $tableNumber }}@endif
@if($cancellationReason)
Motif d'annulation : {{ $cancellationReason }}
@endif

Nous vous présentons nos plus sincères excuses pour ce désagrément. N'hésitez pas à nous contacter ou à soumettre une nouvelle demande de réservation.

Cordialement,
L'équipe du Café Restaurant

Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
