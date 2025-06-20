@php
$primaryColor = $isForAdmin ? '#dc2626' : '#f59e0b';
$backgroundColor = $isForAdmin ? '#fef2f2' : '#fffbeb';
$borderColor = $isForAdmin ? '#fecaca' : '#fef3c7';
$buttonText = $isForAdmin ? 'Gérer la réclamation' : 'Voir ma réclamation';
$buttonRoute = $isForAdmin ? route('admin.disputes.show', $dispute) : route('disputes.show', $dispute);
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isForAdmin ? 'Nouvelle réclamation' : 'Réclamation créée' }} - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
    
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px; background-color: #f8f9fa;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; text-align: left; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: {{ $primaryColor }}; color: #ffffff; text-align: center; padding: 48px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <img src="/storage/trouve-ta-babysitter-logo.svg" alt="{{ config('app.name') }}" style="max-width: 160px; margin-bottom: 24px;">
                                        <h1 style="font-size: 26px; margin: 0; font-weight: 600; letter-spacing: -0.3px;">
                                            {{ $isForAdmin ? 'Nouvelle réclamation créée' : 'Réclamation créée avec succès' }}
                                        </h1>
                                        <p style="margin: 16px 0 0; font-size: 16px; opacity: 0.95; font-weight: 400;">
                                            {{ $isForAdmin ? 'Action requise' : 'Nous traitons votre demande' }}
                                        </p>
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
                                        <p style="margin: 0 0 32px 0; font-size: 18px; color: #2d3748;">
                                            {{ $isForAdmin ? 'Bonjour Admin,' : 'Bonjour ' . $notifiable->firstname . ' !' }}
                                        </p>
                                    </td>
                                </tr>
                                
                                <!-- Information sur la réclamation -->
                                <tr>
                                    <td style="background-color: {{ $backgroundColor }}; border-radius: 12px; padding: 32px; border-left: 4px solid {{ $primaryColor }};">
                                        <h2 style="font-size: 20px; color: {{ $primaryColor }}; margin: 0 0 24px 0; font-weight: 600;">
                                            {{ $isForAdmin ? '⚠️ Réclamation à traiter' : '✅ Réclamation enregistrée' }}
                                        </h2>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            @if($isForAdmin)
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid {{ $borderColor }};">
                                                    <strong style="color: #4a5568;">Créée par :</strong> {{ $dispute->reporter->firstname }} {{ $dispute->reporter->lastname }}
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid {{ $borderColor }};">
                                                    <strong style="color: #4a5568;">Motif :</strong> {{ $dispute->reason }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid {{ $borderColor }};">
                                                    <strong style="color: #4a5568;">Réservation :</strong> #{{ $dispute->reservation->id }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0;">
                                                    <strong style="color: #4a5568;">Date :</strong> {{ $dispute->created_at->format('d/m/Y à H:i') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if($isForAdmin)
                                    <!-- Actions pour admin -->
                                    <tr>
                                        <td style="padding-top: 32px;">
                                            <h3 style="font-size: 18px; color: #2d3748; margin: 0 0 24px 0; font-weight: 600;">🔧 Actions à effectuer</h3>
                                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                                                <tr>
                                                    <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Examiner les détails de la réclamation</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Contacter les parties concernées si nécessaire</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 16px 0;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Prendre une décision et répondre</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @else
                                    <!-- Prochaines étapes pour utilisateur -->
                                    <tr>
                                        <td style="padding-top: 32px;">
                                            <h3 style="font-size: 18px; color: #2d3748; margin: 0 0 24px 0; font-weight: 600;">📋 Prochaines étapes</h3>
                                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                                                <tr>
                                                    <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Notre équipe va examiner votre demande</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Vous recevrez une réponse sous 48h maximum</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 16px 0;">
                                                        <span style="font-size: 16px; color: #2d3748; font-weight: 500;">• Un email de suivi vous sera envoyé</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                <!-- Bouton principal -->
                                <tr>
                                    <td style="text-align: center; padding: 0 0 40px 0;">
                                        <a href="{{ $buttonRoute }}" style="display: inline-block; padding: 16px 32px; background-color: {{ $primaryColor }}; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 8px; font-size: 16px;">
                                            {{ $buttonText }}
                                        </a>
                                    </td>
                                </tr>

                                @if($isForAdmin)
                                    <!-- Urgence -->
                                    <tr>
                                        <td style="background-color: #fffbeb; border-radius: 12px; padding: 32px; border-left: 4px solid #f59e0b;">
                                            <h3 style="font-size: 16px; color: #f59e0b; margin: 0 0 16px 0; font-weight: 600;">⚡ Urgent</h3>
                                            <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">
                                                Merci de traiter cette réclamation rapidement pour maintenir la qualité du service.
                                            </p>
                                        </td>
                                    </tr>
                                @else
                                    <!-- Patience -->
                                    <tr>
                                        <td style="background-color: #f0f9ff; border-radius: 12px; padding: 32px; border-left: 4px solid #3b82f6;">
                                            <h3 style="font-size: 16px; color: #3b82f6; margin: 0 0 16px 0; font-weight: 600;">🙏 Merci pour votre patience</h3>
                                            <p style="margin: 0; font-size: 16px; color: #4a5568; line-height: 1.6;">
                                                Notre équipe prend chaque réclamation au sérieux et s'efforcera de résoudre votre problème dans les meilleurs délais.
                                            </p>
                                        </td>
                                    </tr>
                                @endif

                                <!-- Message de fermeture -->
                                <tr>
                                    <td style="padding-top: 40px;">
                                        <p style="margin: 0; font-size: 16px; color: #4a5568;">
                                            Cordialement,<br>
                                            <strong style="color: #2d3748; font-weight: 600;">{{ $isForAdmin ? 'Système' : 'L\'équipe' }} {{ config('app.name') }}</strong>
                                        </p>
                                    </td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: {{ $primaryColor }}; color: #ffffff; text-align: center; padding: 32px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <p style="margin: 0 0 16px 0; font-size: 14px; opacity: 0.9;">
                                            © {{ now()->year }} {{ config('app.name') }} – Tous droits réservés
                                        </p>
                                        <a href="{{ config('app.url') }}" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: {{ $primaryColor }}; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 14px;">
                                            Retour au site
                                        </a>
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