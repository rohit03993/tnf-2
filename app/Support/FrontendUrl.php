<?php

namespace App\Support;

class FrontendUrl
{
    public static function base(): string
    {
        return rtrim((string) TnfSetting::get('frontend_url', config('app.url')), '/');
    }

    public static function to(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return self::base().'/'.ltrim($path, '/');
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = false): string
    {
        $path = route($name, $parameters, absolute: false);

        return self::to($path);
    }
}
