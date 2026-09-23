<?php

namespace App\Filament\Central\Resources\TenantThemeAvailabilities;

use App\Models\Tenant;
use BackedEnum;
use Filament\Forms;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class TenantThemeAvailabilityResource extends Resource
{
    protected static ?string $model = Tenant::class;

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return 'heroicon-o-swatch';
    }

    public static function getNavigationLabel(): string
    {
        return 'Theme Availability';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'theme-availability';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\CheckboxList::make('themes')
                ->relationship('themes', 'name')
                ->label('Available Themes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Tenant'),
                Tables\Columns\TextColumn::make('themes.name')
                    ->label('Available Themes')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}   