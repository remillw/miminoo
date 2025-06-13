<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Demande de vérification envoyée - {{ config('app.name') }}</title>
  </head>
  <body class="bg-gray-100 text-gray-800 font-sans m-0 p-0">
    <table class="w-full bg-gray-100 p-5" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center">
          <table class="w-[600px] bg-white rounded-lg overflow-hidden text-left" cellpadding="0" cellspacing="0">
            <!-- Header -->
            <tr>
              <td class="bg-primary text-white text-center p-5">
                <img src="/storage/logo_miminoo.png" alt="{{ config('app.name') }}" class="max-w-[150px] mb-2 mx-auto">
                <h1 class="text-2xl m-0">Demande envoyée avec succès</h1>
                <p class="mt-2 mb-0">Nous allons examiner votre profil rapidement 👀</p>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td class="p-5">
                <p class="mb-4">Bonjour {{ $notifiable->firstname }},</p>

                <p class="mb-4">Votre demande de vérification de profil babysitter a bien été envoyée.</p>
                <p class="mb-6">Notre équipe va examiner votre profil dans les plus brefs délais. Vous recevrez un email de confirmation une fois la vérification terminée.</p>

                <h2 class="text-xl text-primary mt-0 mb-3">Que se passe-t-il maintenant ?</h2>
                <ul class="list-none mb-6 space-y-2">
                  <li>✅ <strong>Examen de votre profil</strong> – Notre équipe va vérifier les informations de votre profil</li>
                  <li>📧 <strong>Notification par email</strong> – Vous recevrez une notification une fois la vérification terminée</li>
                  <li>💬 <strong>Support disponible</strong> – En cas de questions, vous pouvez nous contacter à tout moment</li>
                </ul>

                <!-- Panel -->
                <div class="bg-gray-100 border border-gray-300 rounded-md px-4 py-3 text-sm mb-6">
                  <strong>Temps de traitement estimé :</strong> 24–48 heures ouvrables
                </div>

                <p class="mb-0">Merci de votre patience et de faire confiance à <strong>{{ config('app.name') }}</strong> !</p>
                <p class="mb-0 mt-4">Cordialement,<br>L’équipe {{ config('app.name') }}</p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td class="bg-primary text-white text-center p-5">
                <p class="text-sm m-0">© {{ now()->year }} {{ config('app.name') }} – Tous droits réservés</p>
                <a href="{{ config('app.url') }}" class="inline-block mt-2 px-5 py-2 bg-white text-primary font-bold rounded no-underline">Retour au site</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
