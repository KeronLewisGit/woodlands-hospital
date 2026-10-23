<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "A new report is in your portal". Deliberately contains no test names or findings:
 * email is not a private channel, the portal is.
 */
class ResultsReady extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A new report is ready in your Woodlands results portal')
            ->greeting('Hello,')
            ->line('A new report from Woodlands Hospital has been added to your secure results portal.')
            ->line('For your privacy, we do not include results in email. Sign in to view or download it.')
            ->action('Sign in to the results portal', route('portal.login'))
            ->line('Please discuss your results with the doctor who requested them.');
    }
}
