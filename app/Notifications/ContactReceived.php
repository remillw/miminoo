<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contact;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle demande de contact - ' . $this->contact->subject_text)
            ->greeting('Bonjour !')
            ->line('Une nouvelle demande de contact a été reçue sur Miminoo.')
            ->line('**Détails de la demande :**')
            ->line('• **Nom :** ' . $this->contact->name)
            ->line('• **Email :** ' . $this->contact->email)
            ->line('• **Téléphone :** ' . ($this->contact->phone ?: 'Non renseigné'))
            ->line('• **Sujet :** ' . $this->contact->subject_text)
            ->line('• **Date :** ' . $this->contact->created_at->format('d/m/Y à H:i'))
            ->line('')
            ->line('**Message :**')
            ->line($this->contact->message)
            ->action('Voir dans l\'admin', url('/admin/contacts/' . $this->contact->id))
            ->line('Merci de répondre rapidement à cette demande.')
            ->salutation('L\'équipe Miminoo');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_id' => $this->contact->id,
            'name' => $this->contact->name,
            'email' => $this->contact->email,
            'subject' => $this->contact->subject,
            'subject_text' => $this->contact->subject_text,
            'message' => substr($this->contact->message, 0, 100) . (strlen($this->contact->message) > 100 ? '...' : ''),
            'created_at' => $this->contact->created_at->toISOString(),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'contact_received';
    }
}
