<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BabysitterVerificationRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $babysitter)
    {
        Log::info('📧 Création de la notification de vérification', [
            'babysitter_id' => $babysitter->id,
            'babysitter_name' => $babysitter->firstname . ' ' . $babysitter->lastname
        ]);
    }

    public function via($notifiable): array
    {
        Log::info('📮 Canaux de notification utilisés', [
            'channels' => ['mail', 'database'],
            'notifiable_id' => $notifiable->id
        ]);
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('✉️ Préparation de l\'email de vérification', [
            'to' => $notifiable->email,
            'babysitter' => $this->babysitter->firstname . ' ' . $this->babysitter->lastname
        ]);

        $profile = $this->babysitter->babysitterProfile;
        
        return (new MailMessage)
            ->subject('Nouvelle demande de vérification de profil babysitter')
            ->greeting('Bonjour !')
            ->line('Une nouvelle demande de vérification de profil babysitter a été soumise.')
            ->line("**Babysitter :** {$this->babysitter->firstname} {$this->babysitter->lastname}")
            ->line("**Email :** {$this->babysitter->email}")
            ->line("**Expérience :** " . ($profile->experience_years ?? 'Non renseigné') . " années")
            ->line("**Tarif :** " . ($profile->hourly_rate ?? 'Non renseigné') . "€/heure")
            ->action('Voir le profil en admin', url('/admin/babysitter-moderation'))
            ->line('Merci de vérifier ce profil dès que possible.');
    }

    public function toArray($notifiable): array
    {
        return [
            'babysitter_id' => $this->babysitter->id,
            'babysitter_name' => "{$this->babysitter->firstname} {$this->babysitter->lastname}",
            'message' => 'Nouvelle demande de vérification de profil babysitter'
        ];
    }
} 