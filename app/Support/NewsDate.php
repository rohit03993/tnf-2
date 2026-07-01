<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class NewsDate
{
    public const TIMEZONE = 'Asia/Kolkata';

    public static function at(?CarbonInterface $value): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        return $value->copy()->timezone(self::TIMEZONE);
    }

    public static function formatDate(?CarbonInterface $value): ?string
    {
        return self::at($value)?->format('j M Y');
    }

    public static function formatDateTime(?CarbonInterface $value): ?string
    {
        $date = self::at($value);

        if ($date === null) {
            return null;
        }

        return $date->format('j M Y').' · '.$date->format('g:i A');
    }

    public static function iso(?CarbonInterface $value): ?string
    {
        return self::at($value)?->toIso8601String();
    }
}
