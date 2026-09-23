<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'module:Blog'])
    ->prefix('blog')       // <-- was 'api/blog'
    ->name('api.blog.')
    ->group(function () {
        Route::get('/', function () {
            return response()->json([
                'module' => 'Blog',
                'tenant' => tenant()?->getTenantKey(),
                'tenant_initialized' => tenancy()->initialized,
            ]);
        })->name('index');
    });