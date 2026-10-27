Avertissement concernant vos absences

Bonjour {{ $user->name }},

@if($absencesCount >= $threshold)
Conformément à notre règlement, à la suite de {{ $absencesCount }} absence(s) enregistrée(s), vos nouvelles réservations sont désormais temporairement suspendues.

Vous ne pouvez plus effectuer de nouvelles réservations pour le moment. Pour régulariser votre situation, merci de prendre contact avec le restaurant.
@elseif($absencesCount == 4)
Nous vous rappelons que {{ $absencesCount }} absence(s) non justifiée(s) ont été enregistrées à ce jour.

Attention : toute nouvelle absence entraînera l'application automatique de la restriction de réservation (suspension de vos nouvelles réservations).
@elseif($absencesCount == 3)
Nous avons enregistré {{ $absencesCount }} absences non justifiées. Nous vous prions instamment de respecter vos réservations à l'avenir.

Après une nouvelle absence, une restriction de réservation sera automatiquement appliquée.
@elseif($absencesCount == 2)
Une deuxième absence non justifiée vient d'être enregistrée.

Rappel important : une nouvelle absence entraînera une restriction de vos réservations.
@else
Une absence non justifiée vient d'être enregistrée concernant votre réservation.

Nous vous rappelons l'importance de respecter vos réservations ou de nous prévenir en cas d'empêchement.
@endif

Merci de votre compréhension.

Cordialement,
L'équipe du Café Restaurant

Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
