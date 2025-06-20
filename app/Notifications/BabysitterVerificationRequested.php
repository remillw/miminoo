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
            ->view('emails.babysitter-verification-requested', [
                'babysitter' => $this->babysitter,
                'profile' => $profile
            ]);
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