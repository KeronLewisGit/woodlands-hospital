<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** Set-password link for a new results-portal account. Signed, single-purpose, expires in 72 hours. */
class PortalInvitation extends Notification
{
    public const int HOURS = 72;

    public function __construct(public readonly bool $resultsWaiting = false) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function url(Patient $patient): string
    {
        // The invited_at timestamp is part of the signature, so re-sending an invitation retires older links.
        return URL::temporarySignedRoute('portal.invite', now()->addHours(self::HOURS), [
            'patient' => $patient->id,
            'v' => $patient->invited_at->timestamp ?? 0,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Patient $notifiable */
        return (new MailMessage)
            ->subject('Your Woodlands Hospital results portal')
            ->greeting('Hello '.md_plain($notifiable->first_name).',')
            ->line('Woodlands Hospital has opened a secure results portal account for you.'.($this->resultsWaiting ? ' A new report is waiting for you there.' : ''))
            ->line('Choose a password to activate it. The link works for '.self::HOURS.' hours and only once.')
            ->action('Set my password', self::url($notifiable))
            ->line('If you did not expect this email, you can ignore it, or call '.format_phone(setting('phone_main'), true).' to let us know.');
    }
}
