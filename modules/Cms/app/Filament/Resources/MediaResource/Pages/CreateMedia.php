<?php

namespace Modules\Cms\Filament\Resources\MediaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Modules\Cms\Filament\Resources\MediaResource;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = $data['path'];

        // FileUpload stores the path; extract metadata from the stored file
        $disk = Storage::disk('tenant');

        $data['path'] = $path;
        $data['filename'] = basename($path);
        $data['size'] = $disk->size($path);
        $data['mime_type'] = $disk->mimeType($path) ?? 'application/octet-stream';

        return $data;
    }
}