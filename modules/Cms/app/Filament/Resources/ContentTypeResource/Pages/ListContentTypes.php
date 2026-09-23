<?php

namespace Modules\Cms\Filament\Resources\ContentTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\ContentTypeResource;

class ListContentTypes extends ListRecords
{
    protected static string $resource = ContentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}