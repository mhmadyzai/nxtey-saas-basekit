<?php

namespace App\Filament\Central\Resources\TenantThemeAvailabilities\Pages;

use App\Filament\Central\Resources\TenantThemeAvailabilities\TenantThemeAvailabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantThemeAvailabilities extends ListRecords
{
    protected static string $resource = TenantThemeAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
