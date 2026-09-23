<?php

namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
		//if (tenancy()->initialized) {
        //$this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        //$this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }

    public function boot(): void
    {
        //$this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        //$this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}