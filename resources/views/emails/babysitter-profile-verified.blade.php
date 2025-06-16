<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil vérifié - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #333333;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px; background-color: #f4f4f4;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; text-align: left;">
          <!-- Header -->
          <tr>
            <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 20px;">
              <img src="/storage/logo_miminoo.png" alt="{{ config('app.name') }}" style="max-width: 150px; margin-bottom: 10px;">
              <h1 style="font-size: 24px; margin: 0;">Votre profil a été vérifié, {{ $notifiable->firstname }} !</h1>
              <p style="margin: 10px 0 0;">Bienvenue officiellement dans notre communauté de babysitters 💫</p>
            </td>
          </tr>

          <!-- Contenu principal -->
          <tr>
            <td style="padding: 20px;">
              <h2 style="font-size: 20px; color: #FF8157; margin-top: 0;">Vous pouvez maintenant :</h2>
              <ul style="margin: 10px 0 20px 20px; padding: 0;">
                <li>✔️ Postuler aux annonces</li>
                <li>✔️ Recevoir des demandes de garde</li>
                <li>✔️ Gérer vos disponibilités</li>
                <li>✔️ Être rémunéré(e) via notre plateforme</li>
              </ul>

              <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('announcements.index') }}" style="display: inline-block; padding: 12px 24px; background-color: #FF8157; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 6px;">Voir les annonces</a>
              </div>

              <h3 style="font-size: 18px; color: #FF8157; margin: 30px 0 10px;">⚠️ Configurez les paiements</h3>
              <p style="margin: 0 0 20px;">Pour recevoir vos paiements, il est essentiel de compléter votre profil Stripe Connect :</p>

              <div style="text-align: center; margin-bottom: 30px;">
                <a href="{{ route('babysitter.stripe.connect') }}" style="display: inline-block; padding: 12px 24px; background-color: #FF8157; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 6px;">Configurer les paiements</a>
              </div>

              <p style="margin-top: 30px;">Merci pour votre confiance et bienvenue dans l'aventure 🧡</p>
              <p style="margin-bottom: 0;">Cordialement,<br><strong>{{ config('app.name') }}</strong></p>
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
