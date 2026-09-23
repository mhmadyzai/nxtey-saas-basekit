<?php

use Illuminate\Support\Facades\Route;
use AlizHarb\Hookx\HookManager;

Route::middleware(['web', 'module:Blog'])
    ->prefix('blog')
    ->name('blog.')
    ->group(function () {
        Route::livewire('/', 'blog::pages::post.index')->name('index');

        Route::get('/hook-test', function () {
            $manager = HookManager::getInstance();
            $result = $manager->applyFilters('tenant.display_name', 'default');

            return response()->json([
                'filtered' => $result,
                'tenant' => tenant()?->getTenantKey(),
            ]);
        });
    });