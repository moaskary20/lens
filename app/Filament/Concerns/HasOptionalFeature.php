<?php

namespace App\Filament\Concerns;

use App\Support\Feature;
use App\Support\Roles;

trait HasOptionalFeature
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::showsInNavigation()
            && static::featureEnabled()
            && static::staffAllowed();
    }

    protected static function showsInNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return static::featureEnabled() && static::staffAllowed();
    }

    protected static function featureEnabled(): bool
    {
        $key = static::$featureKey ?? null;

        return $key === null || Feature::enabled($key);
    }

    protected static function staffAllowed(): bool
    {
        if (filament()->getCurrentPanel()?->getId() === 'vendor') {
            return false;
        }

        return Roles::staffCan(auth()->user(), static::$staffCapability ?? 'view_operations');
    }
}
