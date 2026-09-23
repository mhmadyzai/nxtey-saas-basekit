<?php

namespace Modules\Cms\Filament\Resources\EntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\EntryResource;

class EditEntry extends EditRecord
{
    protected static string $resource = EntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}