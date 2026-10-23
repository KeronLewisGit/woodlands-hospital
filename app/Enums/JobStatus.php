<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'good',
            self::Draft => 'neutral',
            self::Closed => 'muted',
        };
    }
}
