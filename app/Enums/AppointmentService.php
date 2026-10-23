<?php

namespace App\Enums;

enum AppointmentService: string
{
    case Clinic = 'clinic';
    case Specialist = 'specialist';
    case Radiology = 'radiology';
    case Lab = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Clinic => 'Doctor’s clinic',
            self::Specialist => 'Specialist clinic',
            self::Radiology => 'Radiology & ultrasound',
            self::Lab => 'Medical laboratory',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Clinic => 'A general or follow-up visit with a doctor.',
            self::Specialist => 'A consultant clinic, such as gynaecology or cardiology.',
            self::Radiology => 'X-ray, CT or ultrasound. Bring your doctor’s request form.',
            self::Lab => 'Blood work and other tests. Ask us about fasting before you come.',
        };
    }

    /** The settings key holding the phone extension that handles this service. */
    public function extensionKey(): string
    {
        return match ($this) {
            self::Clinic, self::Specialist => 'ext_clinics',
            self::Radiology => 'ext_radiology',
            self::Lab => 'ext_lab',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Clinic => 'stethoscope',
            self::Specialist => 'users',
            self::Radiology => 'scan',
            self::Lab => 'flask',
        };
    }
}
