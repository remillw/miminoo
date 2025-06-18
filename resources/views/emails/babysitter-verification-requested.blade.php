<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nouvelle demande de vérification - {{ config('app.name') }}</title>
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
                <h1 style="font-size: 24px; margin: 0;">Nouvelle demande de vérification</h1>
                <p style="margin: 10px 0 0;">Un(e) babysitter attend votre validation 🧐</p>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td style="padding: 20px;">
                <p style="margin-bottom: 16px;">Bonjour,</p>
                <p style="margin-bottom: 24px;">Une nouvelle demande de vérification a été soumise par <strong>{{ $babysitter->firstname }} {{ $babysitter->lastname }}</strong>.</p>

                <h2 style="font-size: 20px; color: #FF8157; margin: 24px 0 12px;">Informations du profil :</h2>
                <ul style="margin-left: 20px; margin-bottom: 24px; padding-left: 0;">
                  <li><strong>Email :</strong> {{ $babysitter->email }}</li>
                  <li><strong>Téléphone :</strong> {{ $babysitter->phone }}</li>
                  <li><strong>Expérience :</strong> {{ $babysitter->babysitterProfile->experience_years }} ans</li>
                  <li><strong>Tarif horaire :</strong> {{ $babysitter->babysitterProfile->hourly_rate }} €</li>
                </ul>

                <div style="text-align: center; margin: 24px 0;">
                  <a href="{{ route('admin.babysitters.moderation') }}" style="display: inline-block; padding: 12px 24px; background-color: #FF8157; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 6px;">Voir le profil</a>
                </div>

                <p style="margin-top: 24px;">Merci de vérifier ce profil dans les plus brefs délais.</p>
                <p style="margin-bottom: 0;">Cordialement,<br><strong>{{ config('app.name') }}</strong></p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 20px;">
                <p style="margin: 0; font-size: 14px;">© {{ now()->year }} {{ config('app.name') }} – Espace modération</p>
                <a href="{{ config('app.url') }}" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #ffffff; color: #FF8157; text-decoration: none; font-weight: bold; border-radius: 4px;">Accéder au site</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
