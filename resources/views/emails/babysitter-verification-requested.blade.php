<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nouvelle demande de vérification - {{ config('app.name') }}</title>
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
                <h1 class="text-2xl m-0">Nouvelle demande de vérification</h1>
                <p class="mt-2 mb-0">Un(e) babysitter attend votre validation 🧐</p>
              </td>
            </tr>

            <!-- Contenu principal -->
            <tr>
              <td class="p-5">
                <p class="mb-4">Bonjour,</p>
                <p class="mb-4">Une nouvelle demande de vérification a été soumise par <strong>{{ $babysitter->firstname }} {{ $babysitter->lastname }}</strong>.</p>

                <h2 class="text-xl text-primary mt-6 mb-2">Informations du profil :</h2>
                <ul class="ml-5 mb-6 list-disc">
                  <li><strong>Email :</strong> {{ $babysitter->email }}</li>
                  <li><strong>Téléphone :</strong> {{ $babysitter->phone }}</li>
                  <li><strong>Expérience :</strong> {{ $babysitter->babysitterProfile->experience_years }} ans</li>
                  <li><strong>Tarif horaire :</strong> {{ $babysitter->babysitterProfile->hourly_rate }} €</li>
                </ul>

                <div class="text-center my-6">
                  <a href="{{ route('admin.babysitters.moderation') }}" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-md no-underline">Voir le profil</a>
                </div>

                <p class="mt-6">Merci de vérifier ce profil dans les plus brefs délais.</p>
                <p class="mb-0">Cordialement,<br><strong>{{ config('app.name') }}</strong></p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td class="bg-primary text-white text-center p-5">
                <p class="text-sm m-0">© {{ now()->year }} {{ config('app.name') }} – Espace modération</p>
                <a href="{{ config('app.url') }}" class="inline-block mt-2 px-5 py-2 bg-white text-primary font-bold rounded no-underline">Accéder au site</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
