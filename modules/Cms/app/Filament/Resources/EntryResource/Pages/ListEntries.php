<?php

namespace Modules\Cms\Filament\Resources\EntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\EntryResource;

class ListEntries extends ListRecords
{
    protected static string $resource = EntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}