<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Demande de vérification envoyée - {{ config('app.name') }}</title>
  </head>
  <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #333333;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px; background-color: #f4f4f4;">
      <tr>
        <td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; text-align: left;">
            <!-- Header -->
            <tr>
              <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 20px;">
                <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 150px; margin-bottom: 10px;">
                <h1 style="font-size: 24px; margin: 0;">Demande envoyée avec succès</h1>
                <p style="margin: 10px 0 0;">Nous allons examiner votre profil rapidement 👀</p>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td style="padding: 20px;">
                <p style="margin-bottom: 16px;">Bonjour {{ $notifiable->firstname }},</p>

                <p style="margin-bottom: 16px;">Votre demande de vérification de profil babysitter a bien été envoyée.</p>
                <p style="margin-bottom: 24px;">Notre équipe va examiner votre profil dans les plus brefs délais. Vous recevrez un email de confirmation une fois la vérification terminée.</p>

                <h2 style="font-size: 20px; color: #FF8157; margin: 0 0 16px 0;">Que se passe-t-il maintenant ?</h2>
                <ul style="list-style: none; padding: 0; margin: 0 0 24px 0;">
                  <li style="margin-bottom: 8px;">✅ <strong>Examen de votre profil</strong> – Notre équipe va vérifier les informations de votre profil</li>
                  <li style="margin-bottom: 8px;">📧 <strong>Notification par email</strong> – Vous recevrez une notification une fois la vérification terminée</li>
                  <li>💬 <strong>Support disponible</strong> – En cas de questions, vous pouvez nous contacter à tout moment</li>
                </ul>

                <!-- Panel -->
                <div style="background-color: #f4f4f4; border: 1px solid #cccccc; border-radius: 6px; padding: 12px 16px; font-size: 14px; margin-bottom: 24px;">
                  <strong>Temps de traitement estimé :</strong> 24–48 heures ouvrables
                </div>

                <p style="margin-bottom: 0;">Merci de votre patience et de faire confiance à <strong>{{ config('app.name') }}</strong> !</p>
                <p style="margin-top: 16px;">Cordialement,<br>L’équipe {{ config('app.name') }}</p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 20px;">
                <p style="margin: 0; font-size: 14px;">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                <a href="{{ config('app.url') }}" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #ffffff; color: #FF8157; text-decoration: none; font-weight: bold; border-radius: 4px;">Retour au site</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
