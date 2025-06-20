<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vos fonds ont été libérés - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
          
          <!-- Header -->
          <tr>
            <td style="background-color: #10b981; color: #ffffff; text-align: center; padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                    <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Vos fonds ont été libérés ! 💰</h1>
                    <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Votre paiement arrive bientôt</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Contenu principal -->
          <tr>
            <td style="padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                
                <!-- Message de salutation -->
                <tr>
                  <td>
                    <p style="margin: 0 0 32px 0; font-size: 18px; color: #2d3748;">Bonjour {{ $notifiable->firstname }} !</p>
                  </td>
                </tr>
                
                <!-- Information sur le paiement -->
                <tr>
                  <td style="background-color: #f0fdf4; border-radius: 12px; padding: 32px; border-left: 4px solid #10b981;">
                    <h2 style="font-size: 20px; color: #10b981; margin: 0 0 24px 0; font-weight: 600;">💸 Détails du paiement</h2>
                    
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #dcfce7;">
                          <strong style="color: #4a5568;">Montant libéré :</strong> {{ $reservation->babysitter_amount }}€
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #dcfce7;">
                          <strong style="color: #4a5568;">Service :</strong> Garde d'enfants
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 12px 0;">
                          <strong style="color: #4a5568;">Réservation :</strong> #{{ $reservation->id }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Calendrier de paiement -->
                <tr>
                  <td style="padding-top: 32px;">
                    <h3 style="font-size: 18px; color: #2d3748; margin: 0 0 24px 0; font-weight: 600;">📅 Quand vais-je recevoir mon argent ?</h3>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Les fonds seront disponibles selon le calendrier de paiement Stripe</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Généralement sous 2-7 jours ouvrés</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Virement automatique sur votre compte bancaire</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Bouton principal -->
                <tr>
                  <td style="text-align: center; padding: 0 0 40px 0;">
                    <a href="{{ route('babysitter.payments') }}" style="display: inline-block; padding: 16px 32px; background-color: #10b981; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px;">Voir mes paiements</a>
                  </td>
                </tr>

                <!-- Remerciements -->
                <tr>
                  <td style="background-color: #fffbeb; border-radius: 12px; padding: 32px; border-left: 4px solid #f59e0b;">
                    <h3 style="font-size: 16px; color: #f59e0b; margin: 0 0 16px 0; font-weight: 600;">🏆 Excellent travail !</h3>
                    <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Merci pour votre excellent service ! Votre professionnalisme contribue à faire de notre plateforme un lieu de confiance pour les familles.</p>
                  </td>
                </tr>

                <!-- Message de fermeture -->
                <tr>
                  <td style="padding-top: 40px;">
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Continuez comme ça ! 🌟</p>
                    <p style="margin: 0; font-size: 16px; color: #4a5568;">
                      Cordialement,<br>
                      <strong style="color: #2d3748; font-weight: 600;">L'équipe {{ config('app.name') }}</strong>
                    </p>
                  </td>
                </tr>
                
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #10b981; color: #ffffff; text-align: center; padding: 32px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <p style="margin: 0 0 16px 0; font-size: 14px; opacity: 0.9;">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                    <a href="{{ config('app.url') }}" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: #10b981; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 14px;">Retour au site</a>
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