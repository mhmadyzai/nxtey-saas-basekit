<?php

namespace Modules\Cms\Blocks\Schemas;

use Filament\Forms;

class CtaSchema
{
    public static function components(): array
	{
		return [
			Forms\Components\TextInput::make('data.heading')
				->label('Heading')
				->required()
				->maxLength(150),
			Forms\Components\Textarea::make('data.body')
				->label('Body')
				->rows(2),
			Forms\Components\TextInput::make('data.button_label')
				->label('Button Label')
				->required()
				->maxLength(50),
			Forms\Components\TextInput::make('data.button_url')
				->label('Button URL')
				->required()
				->maxLength(255),
		];
	}
}