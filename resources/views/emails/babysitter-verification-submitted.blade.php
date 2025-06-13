@component('mail::message')
# Demande de vérification envoyée avec succès

Bonjour {{ $notifiable->firstname }},

Votre demande de vérification de profil babysitter a bien été envoyée.

Notre équipe va examiner votre profil dans les plus brefs délais. Vous recevrez un email de confirmation une fois la vérification terminée.

## Que se passe-t-il maintenant ?

✅ **Examen de votre profil** - Notre équipe va vérifier les informations de votre profil  
📧 **Notification par email** - Vous recevrez une notification une fois la vérification terminée  
💬 **Support disponible** - En cas de questions, vous pouvez nous contacter à tout moment  

@component('mail::panel')
**Temps de traitement estimé :** 24-48 heures ouvrables
@endcomponent

Merci de votre patience et de faire confiance à {{ config('app.name') }} !

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent 