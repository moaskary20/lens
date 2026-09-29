<?php

namespace App\Support;

use Filament\Facades\Filament;

class FilamentRedirect
{
    public static function intended(): string
    {
        $home = Filament::getUrl();
        $intended = session()->pull('url.intended', $home);
        $path = parse_url((string) $intended, PHP_URL_PATH) ?: '';
        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        $prefix = '/'.trim((string) $panel->getPath(), '/');

        if ($prefix === '/' || $prefix === '') {
            return $home;
        }

        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            return (string) $intended;
        }

        return $home;
    }
}
