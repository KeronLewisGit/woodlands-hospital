<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\AppointmentRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the patient: confirmed, a new time offered, or cancelled. */
class AppointmentUpdated extends Notification
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
        $when = $a->scheduled_for?->format('l j F Y \a\t g:i a');
        $phone = format_phone(setting('phone_main'), true);
        $ext = setting($a->service->extensionKey());

        $mail = (new MailMessage)->greeting("Hello {$a->first_name},");

        $mail = match ($a->status) {
            AppointmentStatus::Confirmed => $mail->subject("Your appointment is confirmed ({$a->reference})")
                ->line("Your {$a->serviceLabel()} appointment is confirmed for **{$when}**.")
                ->line('Please arrive 15 minutes early with a photo ID'.($a->has_referral ? ' and your doctor’s referral or request form.' : '.')),
            AppointmentStatus::Rescheduled => $mail->subject("A new time for your appointment ({$a->reference})")
                ->line("We could not offer the time you asked for. We can see you on **{$when}** for {$a->serviceLabel()}.")
                ->line('If this time does not suit you, call us and we will find another.'),
            AppointmentStatus::Cancelled => $mail->subject("Your appointment request ({$a->reference})")
                ->line("We are not able to book your {$a->serviceLabel()} request.")
                ->line('Please call us so we can help you another way.'),
            default => $mail->subject("Your appointment request ({$a->reference})")->line('Your request has been updated.'),
        };

        if ($a->message_to_patient) {
            $mail->line('Message from the front desk: '.$a->message_to_patient);
        }

        return $mail
            ->line("Questions: {$phone}".($ext ? ", extension {$ext}" : '').". Please quote {$a->reference}.")
            ->salutation('Woodlands Hospital, '.woodlands('address.street').', Georgetown');
    }
}
