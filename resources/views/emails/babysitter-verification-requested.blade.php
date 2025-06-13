@component('mail::message')
# Nouvelle demande de vérification de profil babysitter

Bonjour,

Une nouvelle demande de vérification a été soumise par {{ $babysitter->firstname }} {{ $babysitter->lastname }}.

**Informations du profil :**
- Email : {{ $babysitter->email }}
- Téléphone : {{ $babysitter->phone }}
- Expérience : {{ $babysitter->babysitterProfile->experience_years }} ans
- Tarif horaire : {{ $babysitter->babysitterProfile->hourly_rate }}€

@component('mail::button', ['url' => route('admin.babysitters.moderation')])
Voir le profil
@endcomponent

Merci de vérifier ce profil dans les plus brefs délais.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 