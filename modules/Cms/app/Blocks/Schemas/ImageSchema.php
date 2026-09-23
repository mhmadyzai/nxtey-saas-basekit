<?php

namespace Modules\Cms\Blocks\Schemas;

use Filament\Forms;
use Modules\Cms\Filament\Components\MediaPicker;

class ImageSchema
{
    public static function components(): array
    {
        return [
            MediaPicker::make('data.media_id')
                ->label('Image')
                ->required(),
            Forms\Components\TextInput::make('data.alt')
                ->label('Alt Text')
                ->maxLength(150),
            Forms\Components\TextInput::make('data.caption')
                ->label('Caption')
                ->maxLength(255),
        ];
    }
}