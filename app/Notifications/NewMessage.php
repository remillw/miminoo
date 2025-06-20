<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Message $message)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = $this->message->sender;
        $conversation = $this->message->conversation;
        
        return (new MailMessage)
            ->subject('Nouveau message reçu') 
            ->view('emails.new-message', [
                'sender' => $sender,
                'conversation' => $conversation,
                'message' => $this->message
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_name' => "{$this->message->sender->firstname} {$this->message->sender->lastname}",
            'message' => 'Nouveau message reçu'
        ];
    }
} 