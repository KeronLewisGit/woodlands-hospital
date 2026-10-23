<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Waiting to confirm',
            self::Confirmed => 'Confirmed',
            self::Rescheduled => 'New time offered',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Colour only where staff must act (requested) or the outcome is final. */
    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'attention',
            self::Confirmed => 'good',
            self::Rescheduled => 'neutral',
            self::Cancelled => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Requested => 'dot',
            self::Confirmed => 'check',
            self::Rescheduled => 'calendar',
            self::Cancelled => 'x',
        };
    }
}
