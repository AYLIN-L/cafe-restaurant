Nous avons bien reçu votre demande de réservation

Bonjour {{ $user->name }},

Nous vous remercions pour votre demande de réservation. Celle-ci a bien été enregistrée et est actuellement en attente de validation par notre équipe.

Détails de votre demande :
- Nom : {{ $user->name }}
- Date : {{ $reservation->reservation_date->format('d/m/Y') }}
- Heure : {{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}
- Nombre de convives : {{ $reservation->guests_count }}
@if($reservation->id)- Référence : #{{ $reservation->id }}@endif

Votre réservation n'est pas encore confirmée. Un e-mail vous sera envoyé dès que notre équipe aura pris une décision concernant votre demande.

Cordialement,
L'équipe du Café Restaurant
Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
