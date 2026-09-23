<?php

namespace App\Filament\Central\Resources\TenantThemeAvailabilities\Pages;

use App\Filament\Central\Resources\TenantThemeAvailabilities\TenantThemeAvailabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantThemeAvailability extends EditRecord
{
    protected static string $resource = TenantThemeAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
