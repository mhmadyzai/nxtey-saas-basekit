<?php

namespace Modules\Cms\Blocks\Schemas;

use Filament\Forms;

class HeroSchema
{
    public static function components(): array
{
    return [
        Forms\Components\TextInput::make('data.heading')
            ->label('Heading')
            ->required()
            ->maxLength(150),
        Forms\Components\TextInput::make('data.subheading')
            ->label('Subheading')
            ->maxLength(255),
        Forms\Components\TextInput::make('data.cta_label')
            ->label('CTA Label')
            ->maxLength(50),
        Forms\Components\TextInput::make('data.cta_url')
            ->label('CTA URL')
            ->maxLength(255),
    ];
}
}