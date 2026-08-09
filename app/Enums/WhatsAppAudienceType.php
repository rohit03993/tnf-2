<?php

namespace App\Enums;

enum WhatsAppAudienceType: string
{
    case OptedIn = 'opted_in';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::OptedIn => 'All WhatsApp opted-in users',
            self::Manual => 'Selected users',
        };
    }
}
