<?php

namespace Modules\Cms\Filament\Resources;

use App\Filament\TenantResource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Cms\Filament\Resources\MediaResource\Pages;
use Modules\Cms\Models\Media;
use UnitEnum;

class MediaResource extends TenantResource
{
    protected static ?string $model = Media::class;

    protected static UnitEnum|string|null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Media';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\FileUpload::make('path')
                ->label('File')
                ->disk('tenant')
                ->directory('uploads')
                ->visibility('public')
                ->required()
                ->maxSize(10240)
                ->image()
                ->imagePreviewHeight('200'),

            Forms\Components\TextInput::make('title')
                ->maxLength(255),

            Forms\Components\TextInput::make('alt')
                ->label('Alt Text')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Preview')
                    ->height(50)
					->getStateUsing(fn ($record) => $record->path ? tenant_asset($record->path) : null),

                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->badge()
                    ->color(fn (string $state) => str_starts_with($state, 'image/') ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('size')
                    ->formatStateUsing(fn (int $state) => number_format($state / 1024, 1) . ' KB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Uploaded'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}