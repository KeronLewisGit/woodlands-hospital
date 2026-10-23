<?php

namespace App\Enums;

enum ResultKind: string
{
    case Lab = 'lab';
    case Radiology = 'radiology';

    public function label(): string
    {
        return match ($this) {
            self::Lab => 'Laboratory',
            self::Radiology => 'Radiology',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Lab => 'flask',
            self::Radiology => 'scan',
        };
    }
}
