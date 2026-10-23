<?php

namespace App\Enums;

enum JobCategory: string
{
    case Nursing = 'nursing';
    case Laboratory = 'laboratory';
    case Radiology = 'radiology';
    case Pharmacy = 'pharmacy';
    case Medical = 'medical';
    case Allied = 'allied';
    case Administration = 'administration';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Nursing => 'Nursing',
            self::Laboratory => 'Laboratory',
            self::Radiology => 'Radiology & imaging',
            self::Pharmacy => 'Pharmacy',
            self::Medical => 'Doctors',
            self::Allied => 'Allied health',
            self::Administration => 'Administration',
            self::Support => 'Support services',
        };
    }

    /** Clinical roles are asked for a professional registration number. */
    public function isClinical(): bool
    {
        return ! in_array($this, [self::Administration, self::Support], true);
    }
}
