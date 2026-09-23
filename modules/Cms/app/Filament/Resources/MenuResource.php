<?php

namespace Modules\Cms\Filament\Resources;

use App\Filament\TenantResource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Cms\Filament\Resources\MenuResource\Pages;
use Modules\Cms\Models\Entry;
use Modules\Cms\Models\Menu;
use UnitEnum;

class MenuResource extends TenantResource
{
    protected static ?string $model = Menu::class;

    protected static UnitEnum|string|null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Menus';
    protected static ?int $navigationSort = 4;

        public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->helperText('Used in code: <x-cms::menu location="..." />'),

            Forms\Components\Select::make('location')
                ->options([
                    'header' => 'Header',
                    'footer' => 'Footer',
                    'sidebar' => 'Sidebar',
                    'mobile' => 'Mobile',
                ])
                ->helperText('Themes render menus by location.'),

            Forms\Components\Repeater::make('items')
                ->label('Menu Items')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'entry' => 'Content Entry',
                            'url' => 'External URL',
                        ])
                        ->default('entry')
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\Select::make('slug')
                        ->label('Entry')
                        ->options(fn () => Entry::query()
                            ->published()
                            ->orderBy('title')
                            ->pluck('title', 'slug')
                            ->toArray())
                        ->searchable()
                        ->required(fn (Get $get) => $get('type') === 'entry')
                        ->visible(fn (Get $get) => $get('type') === 'entry'),

                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->required(fn (Get $get) => $get('type') === 'url')
                        ->visible(fn (Get $get) => $get('type') === 'url'),

                    Forms\Components\Select::make('target')
                        ->options([
                            '_self' => 'Same window',
                            '_blank' => 'New window',
                        ])
                        ->default('_self'),

                    // ➡️ NESTED REPEATER PLACED HERE
                    Forms\Components\Repeater::make('children')
                        ->label('Sub-items')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'entry' => 'Content Entry',
                                    'url' => 'External URL',
                                ])
                                ->default('entry')
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('label')
                                ->required()
                                ->maxLength(100),

                            Forms\Components\Select::make('slug')
                                ->label('Entry')
                                ->options(fn () => Entry::query()
                                    ->published()
                                    ->orderBy('title')
                                    ->pluck('title', 'slug')
                                    ->toArray())
                                ->searchable()
                                ->required(fn (Get $get) => $get('type') === 'entry')
                                ->visible(fn (Get $get) => $get('type') === 'entry'),

                            Forms\Components\TextInput::make('url')
                                ->label('URL')
                                ->required(fn (Get $get) => $get('type') === 'url')
                                ->visible(fn (Get $get) => $get('type') === 'url'),

                            Forms\Components\Select::make('target')
                                ->options([
                                    '_self' => 'Same window',
                                    '_blank' => 'New window',
                                ])
                                ->default('_self'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->reorderable()
                        ->addActionLabel('Add sub-item')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->cloneable()
                ->reorderable()
                ->addActionLabel('Add menu item') // Parent components correctly closed here
				->columnSpanFull(),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('location')->badge(),
                Tables\Columns\TextColumn::make('items')
                    ->label('Items')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' item(s)' : '0'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}