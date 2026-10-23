<?php

namespace App\Notifications;

use App\Models\AppointmentRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the patient: we have your request; it is not booked until we confirm. */
class AppointmentReceived extends Notification
{
    public function __construct(public readonly AppointmentRequest $appointment) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->appointment;
        $ext = setting($a->service->extensionKey());

        return (new MailMessage)
            ->subject("We received your appointment request ({$a->reference})")
            ->greeting("Hello {$a->first_name},")
            ->line("Thank you for your request for {$a->serviceLabel()}. Your reference is **{$a->reference}**.")
            ->line('You asked for '.$a->preferred_date->format('l j F Y').', '.mb_strtolower($a->windowLabel()).'.')
            ->line('**This is not yet a booking.** Our front desk will confirm a time, or offer the nearest available one, by email or phone.')
            ->line('If you need to change anything, call '.format_phone(setting('phone_main'), true).($ext ? ", extension {$ext}" : '').' and quote your reference.')
            ->line('If this is an emergency, do not wait: call '.format_phone(setting('ambulance_phone')).' or come to our Emergency Room, open 24 hours.')
            ->salutation('Woodlands Hospital, '.woodlands('address.street').', Georgetown');
    }
}
