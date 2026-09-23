<?php

namespace App\Filament;

use Filament\Resources\Resource;

abstract class TenantResource extends Resource
{
    public static function canAccess(): bool
    {
        if (! tenancy()->initialized) {
            return false;
        }

        // Infer module name from the class namespace:
        // Modules\Blog\Filament\... → "Blog"
        if (preg_match('#^Modules\\\\([^\\\\]+)\\\\#', static::class, $matches)) {
            return tenant()->hasModule($matches[1]);
        }

        return false;
    }
}