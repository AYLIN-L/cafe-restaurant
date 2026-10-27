Mise à jour concernant votre demande de réservation

Bonjour {{ $user->name }},

Nous sommes au regret de vous informer que votre demande de réservation a été refusée.

Détails de la demande :
- Date : {{ $reservation->reservation_date->format('d/m/Y') }}
- Heure : {{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}
- Nombre de convives : {{ $reservation->guests_count }}
@if($rejectionReason)
Motif : {{ $rejectionReason }}
@endif

Nous vous remercions pour l'intérêt que vous portez à notre établissement. N'hésitez pas à soumettre une nouvelle demande de réservation ultérieurement.

Cordialement,
L'équipe du Café Restaurant

Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
