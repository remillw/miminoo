<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil vérifié - {{ config('app.name') }}</title>
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
                <h1 class="text-2xl m-0">Votre profil a été vérifié, {{ $notifiable->firstname }} !</h1>
                <p class="mt-2 mb-0">Bienvenue officiellement dans notre communauté de babysitters 💫</p>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td class="p-5">
                <h2 class="text-xl text-primary mt-0">Vous pouvez maintenant :</h2>
                <ul class="ml-5 my-4 list-disc">
                  <li>✔️ Postuler aux annonces</li>
                  <li>✔️ Recevoir des demandes de garde</li>
                  <li>✔️ Gérer vos disponibilités</li>
                  <li>✔️ Être rémunéré(e) via notre plateforme</li>
                </ul>

                <div class="text-center my-6">
                  <a href="{{ route('announcements.index') }}" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-md no-underline">Voir les annonces</a>
                </div>

                <h3 class="text-lg text-primary mt-8 mb-2">⚠️ Configurez les paiements</h3>
                <p>Pour recevoir vos paiements, il est essentiel de compléter votre profil Stripe Connect :</p>

                <div class="text-center my-4">
                  <a href="{{ route('babysitter.stripe.connect') }}" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-md no-underline">Configurer les paiements</a>
                </div>

                <p class="mt-8">Merci pour votre confiance et bienvenue dans l'aventure 🧡</p>
                <p class="mb-0">Cordialement,<br><strong>{{ config('app.name') }}</strong></p>
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
