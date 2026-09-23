<?php

namespace Modules\Cms\Filament\Resources;

use App\Filament\TenantResource;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Cms\Filament\Resources\ContentTypeResource\Pages;
use Modules\Cms\Models\ContentType;
use UnitEnum;

class ContentTypeResource extends TenantResource
{
    protected static ?string $model = ContentType::class;

    protected static UnitEnum|string|null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Content Types';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(150),

            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('icon')
                ->placeholder('heroicon-o-document'),

            Forms\Components\Repeater::make('fields')
                ->label('Custom Fields')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('key')->required(),
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\Select::make('type')
                        ->options([
                            'text' => 'Text',
                            'textarea' => 'Textarea',
                            'richtext' => 'Rich Text',
                            'number' => 'Number',
                            'boolean' => 'Boolean',
                            'date' => 'Date',
                            'select' => 'Select',
                        ])
                        ->required(),
                    Forms\Components\TagsInput::make('options')
                        ->label('Options (select only)')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') === 'select'),
                ])
                ->columns(3),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('entries_count')
                    ->counts('entries')
                    ->label('Entries'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentTypes::route('/'),
            'create' => Pages\CreateContentType::route('/create'),
            'edit' => Pages\EditContentType::route('/{record}/edit'),
        ];
    }
}