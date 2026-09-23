<?php

namespace Modules\Cms\Blocks\Schemas;

use Filament\Forms;
use Modules\Cms\Filament\Components\MediaPicker;

class GallerySchema
{
    public static function components(): array
    {
        return [
            Forms\Components\Repeater::make('data.images')
                ->label('Images')
                ->schema([
                    MediaPicker::make('media_id')
                        ->label('Image')
                        ->required(),
                    Forms\Components\TextInput::make('alt')
                        ->label('Alt Text')
                        ->maxLength(150),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}