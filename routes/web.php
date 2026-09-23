<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
    foreach (glob(base_path('modules/*/routes/web.php')) as $file) {
        require $file;
    }
    foreach (glob(base_path('modules/*/routes/api.php')) as $file) {
        require $file;
    }
});
