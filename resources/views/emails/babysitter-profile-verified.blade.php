<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil vérifié - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
          
          <!-- Header minimaliste -->
          <tr>
            <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                    <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Votre profil a été vérifié, {{ $notifiable->firstname }} !</h1>
                    <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Bienvenue officiellement dans notre communauté de babysitters</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Contenu principal -->
          <tr>
            <td style="padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                
                <!-- Titre section -->
                <tr>
                  <td>
                    <h2 style="font-size: 20px; color: #FF8157; margin: 0 0 32px 0; font-weight: 600;">Vous pouvez maintenant :</h2>
                  </td>
                </tr>
                
                <!-- Liste des fonctionnalités -->
                <tr>
                  <td>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Postuler aux annonces</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Recevoir des demandes de garde</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Gérer vos disponibilités</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Être rémunéré(e) via notre plateforme</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Bouton principal -->
                <tr>
                  <td style="text-align: center; padding: 0 0 48px 0;">
                    <a href="{{ route('announcements.index') }}" style="display: inline-block; padding: 16px 32px; background-color: #FF8157; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px;">Voir les annonces</a>
                  </td>
                </tr>

                <!-- Section paiements -->
                <tr>
                  <td style="background-color: #FFE2D7; border-radius: 12px; padding: 32px; border-left: 4px solid #FF8157;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td>
                          <h3 style="font-size: 18px; color: #FF8157; margin: 0 0 16px 0; font-weight: 600;">⚠️ Configurez les paiements</h3>
                          <p style="margin: 0 0 24px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Pour recevoir vos paiements, il est essentiel de compléter votre profil Stripe Connect :</p>
                        </td>
                      </tr>
                      <tr>
                        <td style="text-align: center;">
                          <a href="{{ route('babysitter.stripe.connect') }}" style="display: inline-block; padding: 14px 28px; background-color: #ffffff; color: #FF8157; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px; border: 2px solid #FF8157;">Configurer les paiements</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Message de remerciement -->
                <tr>
                  <td style="padding-top: 40px;">
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Merci pour votre confiance et bienvenue dans l'aventure 🧡</p>
                    <p style="margin: 0; font-size: 16px; color: #4a5568;">
                      Cordialement,<br>
                      <strong style="color: #2d3748; font-weight: 600;">{{ config('app.name') }}</strong>
                    </p>
                  </td>
                </tr>
                
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 32px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <p style="margin: 0 0 16px 0; font-size: 14px; opacity: 0.9;">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                    <a href="{{ config('app.url') }}" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: #FF8157; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 14px;">Retour au site</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>