<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil babysitter rejeté - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
          
          <!-- Header -->
          <tr>
            <td style="background-color: #ef4444; color: #ffffff; text-align: center; padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                    <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Profil en attente de correction</h1>
                    <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Quelques ajustements sont nécessaires</p>
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
                    <p style="margin: 0 0 32px 0; font-size: 18px; color: #2d3748;">Bonjour {{ $notifiable->firstname }},</p>
                  </td>
                </tr>
                
                <!-- Information sur le rejet -->
                <tr>
                  <td style="background-color: #fef2f2; border-radius: 12px; padding: 32px; border-left: 4px solid #ef4444;">
                    <h2 style="font-size: 20px; color: #ef4444; margin: 0 0 24px 0; font-weight: 600;">📋 Profil à corriger</h2>
                    
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Votre profil babysitter nécessite quelques modifications avant d'être validé.</p>
                    
                    <div style="background-color: #ffffff; border-radius: 8px; padding: 20px; margin-top: 20px;">
                      <strong style="color: #ef4444; font-size: 16px;">Raison du rejet :</strong>
                      <p style="margin: 8px 0 0 0; font-size: 16px; color: #4a5568; line-height: 1.6;">{{ $reason }}</p>
                    </div>
                  </td>
                </tr>

                <!-- Étapes à suivre -->
                <tr>
                  <td style="padding-top: 32px;">
                    <h3 style="font-size: 18px; color: #2d3748; margin: 0 0 24px 0; font-weight: 600;">🔧 Comment procéder</h3>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">1. Modifiez votre profil selon les indications</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">2. Vérifiez que toutes les informations sont complètes</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">3. Soumettez une nouvelle demande de vérification</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Bouton principal -->
                <tr>
                  <td style="text-align: center; padding: 0 0 40px 0;">
                    <a href="{{ route('profil') }}" style="display: inline-block; padding: 16px 32px; background-color: #ef4444; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px;">Modifier mon profil</a>
                  </td>
                </tr>

                <!-- Encouragements -->
                <tr>
                  <td style="background-color: #f0f9ff; border-radius: 12px; padding: 32px; border-left: 4px solid #3b82f6;">
                    <h3 style="font-size: 16px; color: #3b82f6; margin: 0 0 16px 0; font-weight: 600;">💪 Ne vous découragez pas !</h3>
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Ce processus de vérification nous permet de maintenir la qualité et la sécurité de notre plateforme.</p>
                    <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Une fois les corrections apportées, votre profil sera réévalué rapidement !</p>
                  </td>
                </tr>

                <!-- Message de fermeture -->
                <tr>
                  <td style="padding-top: 40px;">
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Nous sommes là pour vous accompagner dans ce processus.</p>
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
            <td style="background-color: #ef4444; color: #ffffff; text-align: center; padding: 32px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <p style="margin: 0 0 16px 0; font-size: 14px; opacity: 0.9;">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                    <a href="{{ config('app.url') }}" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: #ef4444; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 14px;">Retour au site</a>
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