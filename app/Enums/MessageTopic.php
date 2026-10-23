<?php

namespace App\Enums;

enum MessageTopic: string
{
    case General = 'general';
    case Billing = 'billing';
    case Records = 'records';
    case Feedback = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General question',
            self::Billing => 'Billing & insurance',
            self::Records => 'Medical records',
            self::Feedback => 'Compliment or complaint',
        };
    }
}
