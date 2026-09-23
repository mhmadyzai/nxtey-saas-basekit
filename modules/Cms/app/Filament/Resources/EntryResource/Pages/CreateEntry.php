<?php

namespace Modules\Cms\Filament\Resources\EntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\EntryResource;

class CreateEntry extends CreateRecord
{
    protected static string $resource = EntryResource::class;
}