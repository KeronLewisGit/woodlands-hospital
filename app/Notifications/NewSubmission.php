<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Generic "something new in the dashboard" email for refills, messages and job applications. */
class NewSubmission extends Notification
{
    /** @param list<string> $lines */
    public function __construct(
        public readonly string $subject,
        public readonly array $lines,
        public readonly string $url,
        public readonly string $action = 'Open in the dashboard',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject);
        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        return $mail->action($this->action, $this->url);
    }
}
