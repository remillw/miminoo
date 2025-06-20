<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Candidature envoyée - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
          
          <!-- Header -->
          <tr>
            <td style="background-color: #FF8157; color: #ffffff; text-align: center; padding: 48px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                    <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Candidature envoyée ✅</h1>
                    <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Votre demande a été transmise avec succès</p>
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
                    <p style="margin: 0 0 32px 0; font-size: 18px; color: #2d3748;">Bonjour {{ $babysitter->firstname }} !</p>
                  </td>
                </tr>
                
                <!-- Confirmation de candidature -->
                <tr>
                  <td style="background-color: #f0fdf4; border-radius: 12px; padding: 32px; border-left: 4px solid #10b981; margin-bottom: 24px;">                   
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #dcfce7;">
                          <strong style="color: #4a5568;">Annonce :</strong> {{ $announcement->title }}
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #dcfce7;">
                          <strong style="color: #4a5568;">Parent :</strong> {{ $parent->firstname }} {{ $parent->lastname }}
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 12px 0;">
                          <strong style="color: #4a5568;">Envoyée le :</strong> {{ $application->created_at->format('d/m/Y à H:i') }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Prochaines étapes -->
                <tr>
                  <td>
                    <h3 style="font-size: 18px; color: #2d3748; margin: 24px 0 24px 0; font-weight: 600;">Prochaines étapes</h3>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">- Le parent va recevoir votre candidature</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">- Il pourra consulter votre profil</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">- Il vous contactera s'il est intéressé</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0;">
                          <span style="font-size: 16px; color: #2d3748; font-weight: 500;">- Vous pourrez discuter et planifier la garde</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Boutons d'action -->
                <tr>
  <td align="center" style="padding-bottom: 16px;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" style="padding-right: 8px;">
          <a href="{{ route('messaging.index') }}"
             style="display: inline-block; width: 200px; padding: 14px 0; background-color: #ffffff; color: #FF8157; font-weight: 600; text-decoration: none; border: 2px solid #FF8157; border-radius: 8px; font-size: 16px; text-align: center;">
            Mes messages
          </a>
        </td>
        <td align="center" style="padding-left: 8px;">
          <a href="{{ route('announcements.show', $announcement) }}"
             style="display: inline-block; width: 200px; padding: 14px 0; background-color: #FF8157; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px; text-align: center;">
            Voir l'annonce
          </a>
        </td>
      </tr>
    </table>
  </td>
</tr>


                <!-- Conseil d'attente -->
                <tr>
                  <td style="background-color: #fefce8; border-radius: 12px; padding: 32px; border-left: 4px solid #eab308;">
                    <h3 style="font-size: 16px; color: #eab308; margin: 0 0 16px 0; font-weight: 600;">💡 En attendant</h3>
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Soyez patient(e) ! Les parents reçoivent parfois plusieurs candidatures et prennent le temps de choisir. N'hésitez pas à postuler à d'autres annonces entre temps.</p>
                    <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Vous serez notifié(e) dès que le parent vous contactera via la messagerie.</p>
                  </td>
                </tr>

                <!-- Message de fermeture -->
                <tr>
                  <td style="padding-top: 40px;">
                    <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568; line-height: 1.6;">Bonne chance pour cette opportunité ! 🍀</p>
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