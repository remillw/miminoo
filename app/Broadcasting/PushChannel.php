<?php

namespace App\Broadcasting;

use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;

class PushChannel
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Envoyer la notification push
     */
    public function send($notifiable, Notification $notification)
    {
        // Vérifier que la notification a une méthode toPush
        if (!method_exists($notification, 'toPush')) {
            return;
        }

        // Appeler la méthode toPush de la notification
        return $notification->toPush($notifiable);
    }
}
