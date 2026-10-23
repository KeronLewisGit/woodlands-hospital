<?php

namespace App\Enums;

enum RefillStatus: string
{
    case New = 'new';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready to collect',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Ready => 'good',
            self::Closed => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Preparing => 'clock',
            self::Ready => 'check',
            self::Closed => 'x',
        };
    }
}
