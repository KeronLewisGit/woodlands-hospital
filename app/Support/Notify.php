<?php

namespace App\Support;

use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Sends email after the response, so a slow or failing mail server never makes a
 * patient wait at a form or lose a submission.
 */
final class Notify
{
    /** @return list<string> */
    public static function recipients(): array
    {
        return array_values(array_filter(self::configured(), fn (string $a) => (bool) filter_var($a, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Entries that are not email addresses (typos), shown on the dashboard.
     *
     * @return list<string>
     */
    public static function invalid(): array
    {
        return array_values(array_diff(self::configured(), self::recipients()));
    }

    /**
     * Dashboard setting first, then WOODLANDS_NOTIFY_EMAIL.
     *
     * @return list<string>
     */
    private static function configured(): array
    {
        $raw = setting('notify_email') ?: (string) config('woodlands.notify');

        return preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** Email the hospital's own inbox(es) about a new submission. */
    public static function office(BaseNotification $notification): void
    {
        $to = self::recipients();
        if ($to === []) {
            Log::warning('No notification email is set (dashboard → Settings); nobody was emailed about a new submission.', ['notification' => $notification::class]);

            return;
        }

        self::later(fn () => Notification::route('mail', $to)->notify($notification));
    }

    /** Email a notifiable model (a portal patient). */
    public static function person(?object $notifiable, BaseNotification $notification): void
    {
        if ($notifiable === null) {
            return;
        }
        self::later(fn () => Notification::send($notifiable, $notification));
    }

    /** Email an address that may be blank. */
    public static function address(?string $email, BaseNotification $notification): void
    {
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::later(fn () => Notification::route('mail', $email)->notify($notification));
        }
    }

    private static function later(callable $send): void
    {
        defer(function () use ($send): void {
            try {
                $send();
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
