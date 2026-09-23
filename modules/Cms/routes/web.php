<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'module:Cms'])
    ->group(function () {
        Route::livewire('/{slug}', 'cms::pages.entry.show')
		->name('cms.entry.show')
		->where('slug', '^(?!sitemap\.xml|robots\.txt|offline|manifest\.webmanifest|service-worker\.js|tenancy|admin|livewire|blog|_theme).*$');
		/* Route::livewire('/{slug}', 'cms::pages.entry.show')
            ->name('cms.entry.show')
            ->where('slug', '[a-z0-9\-]+'); */
    });