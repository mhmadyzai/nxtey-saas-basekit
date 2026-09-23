<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;
use AlizHarb\Hookx\HookManager;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Livewire\Livewire;

class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,
                ])->send(fn (Events\TenantCreated $event) => $event->tenant)
                  ->shouldBeQueued(false),
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(fn (Events\TenantDeleted $event) => $event->tenant)
                  ->shouldBeQueued(false),
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy lifecycle
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],

            Events\BootstrappingTenancy::class => [],

            // Runs AFTER Stancl has swapped the DB, cache, filesystem, etc.
            Events\TenancyBootstrapped::class => [
				function (Events\TenancyBootstrapped $event) {
					// 1. Spatie: scope permission cache per tenant
					$registrar = app(PermissionRegistrar::class);
					$registrar->cacheKey = 'spatie.permission.cache.tenant.'
						. $event->tenancy->tenant->getTenantKey();
					$registrar->forgetCachedPermissions();

					// 2. Theme: activate tenant theme if set
					if ($event->tenancy->tenant->theme) {
						config(['themer.active' => $event->tenancy->tenant->theme]);
					}

					// 3. HookX: reset any hooks from a previous context, then register
					//    tenant-scoped hooks for the current tenant.
					$hookManager = HookManager::getInstance();
					$hookManager->reset();
					$hookManager->setStrictMode(false);

					if ($event->tenancy->tenant->getTenantKey() === 'test1') {
						$hookManager->addFilter('tenant.display_name', function ($name) {
							return 'ALPHA: ' . $name;
						});
					}
				},
			],

            Events\RevertingToCentralContext::class => [],

            // Runs AFTER Stancl has restored the central context
            Events\RevertedToCentralContext::class => [
				function (Events\RevertedToCentralContext $event) {
					// 1. Spatie: restore central cache key
					$registrar = app(PermissionRegistrar::class);
					$registrar->cacheKey = 'spatie.permission.cache';
					$registrar->forgetCachedPermissions();

					// 2. Theme: restore central theme
					config(['themer.active' => env('THEME', 'central')]);

					// 3. HookX: clear tenant-scoped hooks so nothing leaks into the next request
					HookManager::getInstance()->reset();
				},
			],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
		FilePreviewController::$middleware = [
			'web',
			'universal',
			InitializeTenancyByDomain::class,
		];
		Livewire::setUpdateRoute(function ($handle) {
			return Route::post('/livewire/update', $handle)
				->middleware(['web', 'universal', InitializeTenancyByDomain::class]);
		});
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }
                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]
                ->prependToMiddlewarePriority($middleware);
        }
    }
}