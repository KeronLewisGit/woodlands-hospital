<?php

namespace App\Notifications;

use App\Models\RefillRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefillReady extends Notification
{
    public function __construct(public readonly RefillRequest $refill) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your prescription is ready to collect ({$this->refill->reference})")
            ->line("Your refill request {$this->refill->reference} is ready at the Woodlands Hospital pharmacy, ".woodlands('address.street').'.')
            ->line('Please bring a photo ID and your prescription. Questions: WhatsApp '.format_phone(setting('pharmacy_whatsapp')).'.');
    }
}
