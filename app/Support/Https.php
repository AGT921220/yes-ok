<?php

namespace App\Support;

class Https
{
    public static function required(): bool
    {
        if (app()->environment('production')) {
            return true;
        }

        if (! str_starts_with((string) config('app.url'), 'https://') || app()->runningInConsole()) {
            return false;
        }

        return ! in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }

    public static function url(string $url): string
    {
        if (! self::required()) {
            return $url;
        }

        return (string) preg_replace('#^http://#', 'https://', $url);
    }
}
