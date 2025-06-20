<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Demande de vérification envoyée - {{ config('app.name') }}</title>
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
                      <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">Demande envoyée avec succès</h1>
                      <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">Nous allons examiner votre profil rapidement</p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td style="padding: 48px 40px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  
                  <!-- Message d'accueil -->
                  <tr>
                    <td>
                      <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568;">Bonjour {{ $notifiable->firstname }},</p>
                      <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568;">Votre demande de vérification de profil babysitter a bien été envoyée.</p>
                      <p style="margin: 0 0 32px 0; font-size: 16px; color: #4a5568;">Notre équipe va examiner votre profil dans les plus brefs délais. Vous recevrez un email de confirmation une fois la vérification terminée.</p>
                    </td>
                  </tr>

                  <!-- Section processus -->
                  <tr>
                    <td>
                      <h2 style="font-size: 20px; color: #FF8157; margin: 0 0 24px 0; font-weight: 600;">Que se passe-t-il maintenant ?</h2>
                    </td>
                  </tr>
                  
                  <!-- Étapes du processus -->
                  <tr>
                    <td>
                      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                        <tr>
                          <td style="padding: 20px 0; border-bottom: 1px solid #f1f5f9;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                              <tr>
                                <td style="width: 40px; vertical-align: top;">
                                  <div style="display: flex; align-items: center; justify-content: center; font-size: 16px;">✅</div>
                                </td>
                                <td style="padding-left: 16px;">
                                  <strong style="color: #2d3748; font-size: 16px; font-weight: 600;">Examen de votre profil</strong>
                                  <p style="margin: 4px 0 0; color: #4a5568; font-size: 15px;">Notre équipe va vérifier les informations de votre profil</p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 20px 0; border-bottom: 1px solid #f1f5f9;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                              <tr>
                                <td style="width: 40px; vertical-align: top;">
                                  <div style="display: flex; align-items: center; justify-content: center; font-size: 16px;">📧</div>
                                </td>
                                <td style="padding-left: 16px;">
                                  <strong style="color: #2d3748; font-size: 16px; font-weight: 600;">Notification par email</strong>
                                  <p style="margin: 4px 0 0; color: #4a5568; font-size: 15px;">Vous recevrez une notification une fois la vérification terminée</p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding: 20px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                              <tr>
                                <td style="width: 40px; vertical-align: top;">
                                  <div style="display: flex; align-items: center; justify-content: center; font-size: 16px;">💬</div>
                                </td>
                                <td style="padding-left: 16px;">
                                  <strong style="color: #2d3748; font-size: 16px; font-weight: 600;">Support disponible</strong>
<p style="margin: 4px 0 0; color: #4a5568; font-size: 15px;">
  En cas de questions, vous pouvez nous contacter à tout moment par mail : 
  <a href="mailto:contact@trouvetababysitter.fr" style="color: #4a5568; text-decoration: underline;">contact@trouvetababysitter.fr</a>
</p>

                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <!-- Panel temps de traitement -->
                  <tr>
                    <td>
                      <div style="background-color: #FFE2D7; border-radius: 12px; padding: 24px; border-left: 4px solid #FF8157; margin-bottom: 32px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td>
                              <strong style="color: #2d3748; font-size: 16px; font-weight: 600;">Temps de traitement estimé :</strong>
                              <p style="margin: 8px 0 0; color: #4a5568; font-size: 15px;">24–48 heures ouvrables</p>
                            </td>
                          </tr>
                        </table>
                      </div>
                    </td>
                  </tr>

                  <!-- Message de fin -->
                  <tr>
                    <td>
                      <p style="margin: 0 0 16px 0; font-size: 16px; color: #4a5568;">Merci de votre patience et de faire confiance à <strong style="color: #2d3748;">{{ config('app.name') }}</strong> !</p>
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