Votre réservation est confirmée !

Bonjour {{ $user->name }},

Excellente nouvelle ! Votre réservation a été confirmée par notre équipe.

Détails de votre réservation :
- Nom : {{ $user->name }}
- Date : {{ $reservation->reservation_date->format('d/m/Y') }}
- Heure : {{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}
- Nombre de convives : {{ $reservation->guests_count }}
@if($tableNumber)- Table attribuée : Table n° {{ $tableNumber }}@endif
@if($openingTime && $closingTime)
Horaires : Ouvert de {{ $openingTime }} à {{ $closingTime }}.
@endif

Nous vous accueillons avec plaisir. Si vous avez un empêchement, merci de nous prévenir dans les meilleurs délais.

À très bientôt,
L'équipe du Café Restaurant

Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
