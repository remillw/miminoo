<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BabysitterProfileVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre profil babysitter a été vérifié !')
            ->greeting('Félicitations ' . $notifiable->firstname . ' !')
            ->line('Votre profil babysitter a été vérifié avec succès.')
            ->line('🎉 **Vous pouvez maintenant postuler aux annonces et commencer à travailler.**')
            ->line('💳 **Compte de paiement créé** - Nous avons automatiquement créé votre compte de paiement pour recevoir vos rémunérations.')
            ->line('⚡ **Prochaines étapes :**')
            ->line('• Configurez vos informations bancaires dans votre profil')
            ->line('• Commencez à postuler aux annonces')
            ->line('• Recevez vos paiements de manière sécurisée')
            ->action('Voir les annonces', route('announcements.index'))
            ->line('Merci de faire confiance à ' . config('app.name') . ' !');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre profil babysitter a été vérifié'
        ];
    }
} 