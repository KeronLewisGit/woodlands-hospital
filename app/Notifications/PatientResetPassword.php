<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientResetPassword extends Notification
{
    public function __construct(#[\SensitiveParameter] public readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('portal.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Reset your Woodlands results portal password')
            ->line('Someone asked to reset the password for your Woodlands Hospital results portal.')
            ->action('Choose a new password', $url)
            ->line('The link expires in 60 minutes. If you did not ask for this, you can ignore this email; your password stays the same.');
    }
}
