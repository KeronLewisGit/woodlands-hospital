<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Contacted = 'contacted';
    case Hired = 'hired';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Reviewing',
            self::Contacted => 'Contacted',
            self::Hired => 'Hired',
            self::Declined => 'Declined',
        };
    }

    /** Colour is reserved for state that needs attention or is final; the rest stay neutral. */
    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Hired => 'good',
            self::Declined => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Reviewing => 'eye',
            self::Contacted => 'phone',
            self::Hired => 'check',
            self::Declined => 'x',
        };
    }
}
