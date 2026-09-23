<?php

namespace Modules\Cms\Filament\Resources\ContentTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\ContentTypeResource;

class EditContentType extends EditRecord
{
    protected static string $resource = ContentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}