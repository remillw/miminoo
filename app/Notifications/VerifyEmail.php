<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The callback that should be used to create the verify email URL.
     *
     * @var \Closure|null
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Build the mail message.
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Confirmez votre adresse email - Trouve ta Babysitter')
            ->greeting('Bienvenue sur Trouve ta Babysitter ! 👋')
            ->line('Nous sommes ravis de vous accueillir dans notre communauté de confiance.')
            ->line('Pour finaliser votre inscription et accéder à toutes les fonctionnalités, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :')
            ->action('✅ Confirmer mon email', $url)
            ->line('Ce lien est valide pendant 60 minutes pour des raisons de sécurité.')
            ->line('')
            ->line('**Pourquoi vérifier votre email ?**')
            ->line('• 🔐 **Sécurité** : Protéger votre compte')
            ->line('• 📧 **Notifications** : Recevoir les messages importants')
            ->line('• ✨ **Accès complet** : Débloquer toutes les fonctionnalités')
            ->line('')
            ->line('Si vous n\'avez pas créé de compte sur Trouve ta Babysitter, vous pouvez ignorer cet email en toute sécurité.')
            ->line('')
            ->line('**Besoin d\'aide ?** Notre équipe support est là pour vous : [support@trouvetababysitter.fr](mailto:support@trouvetababysitter.fr)')
            ->salutation('À très bientôt sur Trouve ta Babysitter ! 🚀')
            ->with([
                'displayableActionUrl' => $url,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Set a callback that should be used when creating the email verification URL.
     */
    public static function createUrlUsing(callable $callback): void
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     */
    public static function toMailUsing(callable $callback): void
    {
        static::$toMailCallback = $callback;
    }
}