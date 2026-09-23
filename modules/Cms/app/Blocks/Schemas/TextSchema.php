<?php

namespace Modules\Cms\Blocks\Schemas;

use Filament\Forms;

class TextSchema
{
    public static function components(): array
	{
		return [
			Forms\Components\RichEditor::make('data.body')
				->label('Body')
				->required()
				->columnSpanFull(),
		];
	}
}