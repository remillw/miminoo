<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nouvelle demande de vérification - {{ config('app.name') }}</title>
  </head>
  <body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
      <tr>
        <td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
            
            <!-- Header -->
            <tr>
              <td style="background-color: #dc2626; color: #ffffff; text-align: center; padding: 48px 40px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td align="center">
                      <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                      <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Nouvelle demande de vérification</h1>
                      <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Un profil babysitter attend votre validation</p>
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
                      <p style="margin: 0 0 32px 0; font-size: 18px; color: #2d3748;">Bonjour Admin,</p>
                    </td>
                  </tr>
                  
                  <!-- Information sur la demande -->
                  <tr>
                    <td style="background-color: #fef2f2; border-radius: 12px; padding: 32px; border-left: 4px solid #dc2626;">
                      <h2 style="font-size: 20px; color: #dc2626; margin: 0 0 24px 0; font-weight: 600;">👤 Profil à vérifier</h2>
                      
                      <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                          <td style="padding: 12px 0; border-bottom: 1px solid #fecaca;">
                            <strong style="color: #4a5568;">Babysitter :</strong> {{ $babysitter->firstname }} {{ $babysitter->lastname }}
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 12px 0; border-bottom: 1px solid #fecaca;">
                            <strong style="color: #4a5568;">Email :</strong> {{ $babysitter->email }}
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 12px 0; border-bottom: 1px solid #fecaca;">
                            <strong style="color: #4a5568;">Expérience :</strong> {{ $profile->experience_years ?? 'Non renseigné' }} années
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 12px 0;">
                            <strong style="color: #4a5568;">Tarif :</strong> {{ $profile->hourly_rate ?? 'Non renseigné' }}€/heure
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <!-- Actions à effectuer -->
                  <tr>
                    <td style="padding-top: 32px;">
                      <h3 style="font-size: 18px; color: #2d3748; margin: 0 0 24px 0; font-weight: 600;">🔍 Actions à effectuer</h3>
                      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                        <tr>
                          <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                            <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Vérifier les informations du profil</span>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                            <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Contrôler les documents fournis</span>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 16px 0;">
                            <span style="font-size: 16px; color: #2d3748; font-weight: 500;">✓ Approuver ou rejeter le profil</span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <!-- Bouton principal -->
                  <tr>
                    <td style="text-align: center; padding: 0 0 40px 0;">
                      <a href="{{ url('/admin/babysitter-moderation') }}" style="display: inline-block; padding: 16px 32px; background-color: #dc2626; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px;">Voir le profil en admin</a>
                    </td>
                  </tr>

                  <!-- Urgence -->
                  <tr>
                    <td style="background-color: #fffbeb; border-radius: 12px; padding: 32px; border-left: 4px solid #f59e0b;">
                      <h3 style="font-size: 16px; color: #f59e0b; margin: 0 0 16px 0; font-weight: 600;">⚡ Urgent</h3>
                      <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Merci de vérifier ce profil dès que possible. Les babysitters attendent votre validation pour commencer à travailler sur la plateforme.</p>
                    </td>
                  </tr>

                  <!-- Message de fermeture -->
                  <tr>
                    <td style="padding-top: 40px;">
                      <p style="margin: 0; font-size: 16px; color: #4a5568;">
                        Cordialement,<br>
                        <strong style="color: #2d3748; font-weight: 600;">Système {{ config('app.name') }}</strong>
                      </p>
                    </td>
                  </tr>
                  
                </table>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background-color: #dc2626; color: #ffffff; text-align: center; padding: 32px 40px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td align="center">
                      <p style="margin: 0 0 16px 0; font-size: 14px; opacity: 0.9;">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                      <a href="{{ config('app.url') }}" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: #dc2626; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 14px;">Retour au site</a>
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