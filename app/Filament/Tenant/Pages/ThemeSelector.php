<?php

namespace App\Filament\Tenant\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ThemeSelector extends Page
{
    protected string $view = 'filament.tenant.pages.theme-selector';

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return 'heroicon-o-swatch';
    }

    public static function getNavigationLabel(): string
    {
        return 'Appearance';
    }

    public function getTitle(): string
    {
        return 'Theme Selection';
    }

    public function getAvailableThemesProperty()
    {
        return tenant()->themes()
            ->wherePivot('enabled', true)
            ->get();
    }

    public function selectTheme(string $theme): void
    {
        $tenant = tenant();

        if (! $tenant->hasTheme($theme)) {
            Notification::make()
                ->title('Theme not available')
                ->danger()
                ->send();
            return;
        }

        $tenant->update(['theme' => $theme]);

        Notification::make()
            ->title('Theme activated')
            ->success()
            ->send();

        $this->redirect(request()->url());
    }
}