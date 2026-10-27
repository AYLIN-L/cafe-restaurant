<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre réservation est confirmée</title>
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
                            <h1 style="margin: 0 0 16px 0; font-size: 24px; color: #2f7c32;">Votre réservation est confirmée !</h1>
                            <p style="margin: 0 0 16px 0; color: #5a4a3b; line-height: 1.6;">Bonjour {{ $user->name }},</p>
                            <p style="margin: 0 0 20px 0; color: #5a4a3b; line-height: 1.6;">Excellente nouvelle ! Votre réservation a été confirmée par notre équipe.</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf6f1; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="font-weight: bold; color: #3e2f23; margin-bottom: 12px;">Détails de votre réservation</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b5b4b; width: 40%;">Nom</td>
                                                <td style="padding: 6px 0; color: #3e2f23; font-weight: 500;">{{ $user->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b5b4b;">Date</td>
                                                <td style="padding: 6px 0; color: #3e2f23; font-weight: 500;">{{ $reservation->reservation_date->format('d/m/Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b5b4b;">Heure</td>
                                                <td style="padding: 6px 0; color: #3e2f23; font-weight: 500;">{{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b5b4b;">Nombre de convives</td>
                                                <td style="padding: 6px 0; color: #3e2f23; font-weight: 500;">{{ $reservation->guests_count }}</td>
                                            </tr>
                                            @if($tableNumber)
                                            <tr>
                                                <td style="padding: 10px 0 6px 0; color: #6b5b4b;">Table attribuée</td>
                                                <td style="padding: 10px 0 6px 0;">
                                                    <span style="display: inline-block; background-color: #6b4f36; color: #f5efe6; padding: 8px 16px; border-radius: 8px; font-weight: bold; font-size: 16px;">Table n° {{ $tableNumber }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($openingTime && $closingTime)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f7f3ee; border: 1px solid #e6d9cc; border-radius: 10px; margin-bottom: 18px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="font-weight: bold; color: #3e2f23; margin-bottom: 8px;">Horaires &amp; informations pratiques</div>
                                        <p style="margin: 0; color: #5a4a3b; line-height: 1.6;">Ouvert de {{ $openingTime }} à {{ $closingTime }}.</p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin: 0 0 12px 0; color: #5a4a3b; line-height: 1.6;">Nous vous accueillons avec plaisir. Si vous avez un empêchement, merci de nous prévenir dans les meilleurs délais.</p>
                            <p style="margin: 24px 0 0 0; color: #5a4a3b; line-height: 1.6;">À très bientôt,<br><strong style="color: #3e2f23;">L'équipe du Café Restaurant</strong></p>
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
