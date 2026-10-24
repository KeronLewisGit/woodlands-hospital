<?php

namespace App\Notifications;

use App\Models\AppointmentRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the hospital: a new request is waiting to be confirmed. */
class NewAppointmentRequest extends Notification
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

        return (new MailMessage)
            ->subject("Appointment request {$a->reference}: {$a->serviceLabel()}")
            ->line(md_plain($a->full_name)." asked for {$a->serviceLabel()}.")
            ->line('Preferred: '.$a->preferred_date->format('l j F Y').', '.mb_strtolower($a->windowLabel()).'.')
            ->line('Phone: '.format_phone($a->phone, true))
            ->action('Confirm or offer a time', route('admin.appointments.show', $a));
    }
}
