<?php

namespace App\Enums;

/** Staff roles. Admin sees everything; front desk runs bookings; lab & radiology publish results. */
enum Role: string
{
    case Admin = 'admin';
    case FrontDesk = 'front_desk';
    case Lab = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::FrontDesk => 'Front desk',
            self::Lab => 'Lab & radiology',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts, settings, doctors and careers.',
            self::FrontDesk => 'Appointment requests, pharmacy refills, messages and patient portal invitations.',
            self::Lab => 'Patient portal records and result uploads.',
        };
    }
}
