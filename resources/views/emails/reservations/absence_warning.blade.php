<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avertissement concernant vos absences</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f3ee; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border: 1px solid #e6d9cc; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background: linear-gradient(90deg, #6b4f36 0%, #8b6b4a 100%); padding: 24px; text-align: center;">
                            <div style="color: #f5efe6; font-size: 22px; font-weight: bold; letter-spacing: 0.5px;">Café Restaurant</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 28px;">
                            @if($absencesCount >= $threshold)
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; color: #b94a48;">Restriction de réservation appliquée</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 18px 0; color: #5a4a3b; line-height: 1.6;">Conformément à notre règlement, à la suite de <strong>{{ $absencesCount }}</strong> absence(s) enregistrée(s), vos nouvelles réservations sont désormais <strong>temporairement suspendues</strong>.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fdf3f2; border: 1px solid #f0c9c7; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="color: #7a2e2c; line-height: 1.6;">Vous ne pouvez plus effectuer de nouvelles réservations pour le moment. Pour régulariser votre situation, merci de prendre contact avec le restaurant.</div>
                                    </td>
                                </tr>
                            </table>
                            @elseif($absencesCount == 4)
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; color: #b98a56;">Dernier avertissement avant restriction</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 18px 0; color: #5a4a3b; line-height: 1.6;">Nous vous rappelons que <strong>{{ $absencesCount }}</strong> absence(s) non justifiée(s) ont été enregistrées à ce jour.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf6f1; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="color: #5a4a3b; line-height: 1.6;"><strong>Attention :</strong> toute nouvelle absence entraînera l'application automatique de la restriction de réservation (suspension de vos nouvelles réservations).</div>
                                    </td>
                                </tr>
                            </table>
                            @elseif($absencesCount == 3)
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; color: #b98a56;">Avertissement important concernant vos absences</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 18px 0; color: #5a4a3b; line-height: 1.6;">Nous avons enregistré <strong>{{ $absencesCount }}</strong> absences non justifiées. Nous vous prions instamment de respecter vos réservations à l'avenir.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf6f1; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="color: #5a4a3b; line-height: 1.6;">Après une nouvelle absence, une restriction de réservation sera automatiquement appliquée.</div>
                                    </td>
                                </tr>
                            </table>
                            @elseif($absencesCount == 2)
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; color: #b98a56;">Deuxième avertissement pour absence non justifiée</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 18px 0; color: #5a4a3b; line-height: 1.6;">Une deuxième absence non justifiée vient d'être enregistrée.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf6f1; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="color: #5a4a3b; line-height: 1.6;"><strong>Rappel important :</strong> une nouvelle absence entraînera une restriction de vos réservations.</div>
                                    </td>
                                </tr>
                            </table>
                            @else
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; color: #3e2f23;">Absence enregistrée - Rappel du règlement</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 18px 0; color: #5a4a3b; line-height: 1.6;">Une absence non justifiée vient d'être enregistrée concernant votre réservation.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf6f1; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="color: #5a4a3b; line-height: 1.6;">Nous vous rappelons l'importance de respecter vos réservations ou de nous prévenir en cas d'empêchement.</div>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Merci de votre compréhension.</p>
                            <p style="margin: 24px 0 0 0; color: #5a4a3b; line-height: 1.6;">Cordialement,<br><strong style="color: #3e2f23;">L'équipe du Café Restaurant</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f7f3ee; padding: 16px 28px; text-align: center; color: #8a7a6a; font-size: 12px;">
                            Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
