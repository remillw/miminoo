@component('mail::message')
# Votre profil babysitter a été vérifié !

Félicitations {{ $notifiable->firstname }} !

Votre profil babysitter a été vérifié avec succès. Vous pouvez maintenant :
- Postuler aux annonces
- Recevoir des demandes de garde
- Gérer vos disponibilités
- Recevoir des paiements via notre plateforme

@component('mail::button', ['url' => route('announcements.index')])
Voir les annonces
@endcomponent

Pour commencer à recevoir des paiements, vous devez compléter votre profil Stripe Connect. Cliquez sur le bouton ci-dessous :

@component('mail::button', ['url' => route('babysitter.stripe.connect')])
Configurer les paiements
@endcomponent

Merci de votre confiance !

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 