# nxtsaasnet — Architecture Reference

**Last updated:** after Step 4 (Themes) — all four steps verified working.
**Stack version:** Laravel (latest), PHP 8.x, MySQL (Laragon on Windows).

---

## 1. Overview

Multi-tenant SaaS built as an API-first, PWA-ready modular system with
database-per-tenant isolation.

Core decisions:

| Concern | Choice |
|---------|--------|
| Tenancy | `stancl/tenancy` — database per tenant |
| Auth & permissions | `spatie/laravel-permission` in teams mode, string `team_id` |
| Modularity | `alizharb/laravel-modular`, opt-in per tenant |
| Theming | `alizharb/laravel-themer`, per-tenant themes |
| Hooks | `alizharb/laravel-hooks` (Step 5) |
| Admin UI | `alizharb/laravel-modular-filament` (Step 6) |
| Reactive UI | `alizharb/laravel-modular-livewire` (Step 7) |
| User model | Central identity + per-tenant mirror (Option C) |
| Central app theming | Option A — central is itself a theme |
| Modules per tenant | Option B — opt-in via central pivot |
| Environments | Local: Laragon + Windows, hosts file entries |
| Domain convention | Central: `nxtsaasnet.test`, Tenants: `<tenant>.nxtsaasnet.test` |

---

app/
├── Http/
│   └── Middleware/
│       └── EnsureTenantHasModule.php   # gates module routes per tenant
├── Models/
│   ├── User.php                        # central identity
│   ├── Tenant.php                      # Stancl tenant model
│   ├── TenantUser.php                  # per-tenant mirror user
│   └── Module.php                      # central module registry
├── Providers/
│   ├── AppServiceProvider.php
│   └── TenancyServiceProvider.php      # tenancy event wiring (see §6)
└── Services/
    └── TenantTeamResolver.php          # Spatie team resolver (see §5)

config/
├── database.php                        # central + tenant connections
├── tenancy.php                         # tenancy config (see §3)
├── permission.php                      # teams mode + custom resolver
├── modular.php                         # discovery.routes = false (see §7)
└── themer.php                          # themes_path, active, discovery

database/
├── migrations/                         # central migrations
│   ├── 2019_09_15_000010_create_tenants_table.php
│   ├── 2019_09_15_000020_create_domains_table.php
│   ├── ..._create_users_table.php
│   ├── ..._create_tenant_user_table.php
│   ├── ..._create_modules_table.php
│   ├── ..._create_tenant_module_table.php
│   └── ..._add_theme_to_tenants_table.php
└── migrations/tenant/                  # tenant migrations (run per tenant)
    ├── ..._create_users_table.php
    ├── ..._create_permission_tables.php
    └── ..._create_posts_table.php      # Blog module, example

modules/
└── Blog/
    ├── module.json
    ├── composer.json
    ├── app/Providers/
    │   └── BlogServiceProvider.php
    └── routes/
        ├── web.php
        └── api.php

themes/
├── central/                            # central theme + tenant fallback
└── alpha/                              # tenant theme (assigned to test1)

routes/
├── web.php                             # central web routes
├── api.php                             # central api routes
└── tenant.php                          # ALL tenant routes (incl. modules)   

docs/
ARCHITECTURE.md # this file


---

## 3. Tenancy (Step 1)

### Connections (`config/database.php`)

| Name | Purpose | Database |
|------|---------|----------|
| `central` | Central DB (tenants, users, modules) | `nxtsaasnetdb` |
| `tenant` | Template connection, cloned and DB-swapped by Stancl | `null` (dynamic) |

Env:

```env
DB_CONNECTION=central
DB_DATABASE=nxtsaasnetdb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
```
## Tenancy config (config/tenancy.php)
Key values:
```
'tenant_model' => \App\Models\Tenant::class,

'central_domains' => [
    '127.0.0.1',
    'localhost',
    'nxtsaasnet.test',
],

'database' => [
    'central_connection' => env('DB_CONNECTION', 'central'),
    'template_tenant_connection' => null,   // fall back to `tenant`
    'prefix' => 'tenant_',
    'suffix' => '',
    'managers' => [
        'mysql' => MySQLDatabaseManager::class,
        // ...
    ],
],

'migration_parameters' => [
    '--force' => true,
    '--path' => [database_path('migrations/tenant')],
    '--realpath' => true,
],
```

## Tenant model (app/Models/Tenant.php)
```
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function modules(): BelongsToMany { /* pivot: tenant_module */ }

    public function hasModule(string $module): bool
    {
        return $this->modules()
            ->wherePivot('enabled', true)
            ->where('modules.name', $module)
            ->exists();
    }
}
```

## Provisioning
Triggered automatically by TenantCreated event via JobPipeline:

1. CreateDatabase — creates tenant_<id>

2. MigrateDatabase — runs database/migrations/tenant/*

TenantDeleted triggers DeleteDatabase.

## 4. Users & Permissions (Step 2 — Option C)
### Tables
Central:
* users — cross-tenant identities
* tenant_user — pivot: user ↔ tenant

### Tenant (per DB):
* users — per-tenant mirror with central_user_id
* roles, permissions, model_has_roles, model_has_permissions, role_has_permissions

### Guard / Team Keys
* Guard: web — set explicitly via protected $guard_name = 'web'; on TenantUser
* Team: team_id is a string column (matches UUID tenant keys)
* config/permission.php:
```
'teams' => true,
'column_names' => ['team_foreign_key' => 'team_id'],
'team_resolver' => \App\Services\TenantTeamResolver::class,
'cache' => ['store' => 'array'],   // prevents cross-tenant cache leak
```

## TenantTeamResolver
Auto-detects the tenant from the Stancl context when Spatie requests
the current team ID:
```
public function getPermissionsTeamId(): int|string|null
{
    if ($this->teamId === null && tenancy()->initialized) {
        return tenant()->getTenantKey();
    }
    return $this->teamId;
}
```
## Models
* App\Models\User — $connection = 'central', HasRoles NOT applied here
* App\Models\TenantUser — uses active tenant connection, HasRoles, $guard_name = 'web'

## Verified
* test1 role count: 1 (admin)
* test2 role count: 1 (viewer) — no cross-tenant leak
* TenantUser->assignRole('admin') → succeeds
* TenantUser->hasRole('admin') → true

## 5. Modules (Step 3 — Opt-In Per Tenant)
### Central tables
modules — registry of available modules (name, version, enabled_globally)

tenant_module — pivot (tenant_id, module, enabled, settings)

### Config
config/modular.php:
```
'discovery' => [
    'configs' => true,
    'views' => true,
    'translations' => true,
    'migrations' => true,
    'routes' => false,    // <-- disabled; routes loaded manually in routes/tenant.php
    'blade_components' => true,
],
```

## Route Loading
Module routes are not auto-loaded. They are required inside
routes/tenant.php:
```
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    foreach (glob(base_path('modules/*/routes/web.php')) as $file) {
        require $file;
    }
});

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api')->group(function () {
    foreach (glob(base_path('modules/*/routes/api.php')) as $file) {
        require $file;
    }
});
```
## Module Provider
Each module's app/Providers/<Name>ServiceProvider.php:
* Namespace: Modules\<Name>\Providers
* Empty boot() — do not load routes; tenancy must gate them

## Route Gating
Every module route includes middleware(['module:<Name>']).
Middleware app/Http/Middleware/EnsureTenantHasModule.php:
```
if (! tenancy()->initialized) abort(404);
if (! tenant()->hasModule($module)) abort(404);
```
### Aliased in bootstrap/app.php:
```
$middleware->alias([
    'module' => \App\Http\Middleware\EnsureTenantHasModule::class,
]);
```

## Verified
* test1.nxtsaasnet.test/blog → {"module":"Blog","tenant":"test1", ...}
* test1.nxtsaasnet.test/api/blog → same
* test2.nxtsaasnet.test/blog → Laravel 404 (module not enabled)
* Tenant::find('test1')->hasModule('Blog') → true
* Tenant::find('test2')->hasModule('Blog') → false

## 6. Themes (Step 4 — Option A)
### Approach
Central is a theme named central. Every rendering context resolves
through the themer. Tenants override via tenants.theme. Fallback is
THEME env → central.

### Layout
themes/
├── central/                            # central theme + tenant fallback
│   ├── theme.json
│   ├── ThemeServiceProvider.php
│   ├── resources/
│   │   ├── views/
│   │   └── assets/
│   ├── vite.config.js
│   └── package.json
└── alpha/                              # tenant theme (assigned to test1)
    └── ...                             # same structure as central/   

## Config (config/themer.php)
```
'themes_path' => base_path('themes'),
'active' => (string) env('THEME', 'default'),
'assets' => [
    'symlink' => (bool) env('THEMER_SYMLINK', true),
],
'discovery' => [
    'filename' => 'theme.json',
    'scan_modules' => true,
],
```
Runtime key for override: themer.active.

## .env
```
THEME=central
THEMER_SYMLINK=false    # Windows: avoid admin-privilege symlink failures
```

## Central DB
* tenants.theme (string, nullable)
 * null → falls back to THEME env (central)

## Runtime Switching
In app/Providers/TenancyServiceProvider.php:
```
Events\TenancyBootstrapped::class => [
    function (Events\TenancyBootstrapped $event) {
        // Spatie: scope permission cache per tenant
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'
            . $event->tenancy->tenant->getTenantKey();
        $registrar->forgetCachedPermissions();

        // Theme: activate tenant theme if set
        if ($event->tenancy->tenant->theme) {
            config(['themer.active' => $event->tenancy->tenant->theme]);
        }
    },
],

Events\RevertedToCentralContext::class => [
    function (Events\RevertedToCentralContext $event) {
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache';
        $registrar->forgetCachedPermissions();

        config(['themer.active' => env('THEME', 'central')]);
    },
],
```

## Verification Endpoint
GET /_theme (inside routes/tenant.php tenant group) returns:
```
{ "tenant": "...", "tenant_theme": "...", "themer_active": "..." }
```

## Verified
* test1.nxtsaasnet.test/_theme → tenant: test1, theme: alpha, active: alpha
* test2.nxtsaasnet.test/_theme → tenant: test2, theme: null, active: central
* nxtsaasnet.test/_theme → Laravel 404

## 7. Tenancy Event Wiring
File: app/Providers/TenancyServiceProvider.php
Critical imports:
```
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;
```

Wired events (summary):
|Event|Listeners / Jobs|
|-----|----------------|
|TenantCreated|	CreateDatabase, MigrateDatabase (JobPipeline)|
|TenantDeleted|	DeleteDatabase (JobPipeline)|
|TenancyInitialized|	BootstrapTenancy|
|TenancyBootstrapped|	inline: Spatie cache scope + theme override|
|RevertedToCentralContext|	inline: Spatie cache restore + theme restore|
|SyncedResourceSaved|	UpdateSyncedResource|
---
Pitfall: do not duplicate event keys in the array — later keys
silently overwrite earlier keys. All wiring must be in one place.

## 8. Local Development Setup
### Hosts file
```
127.0.0.1  nxtsaasnet.test
127.0.0.1  test1.nxtsaasnet.test
127.0.0.1  test2.nxtsaasnet.test
```
### Commands
```
php artisan optimize:clear            # after any config change
php artisan tenants:migrate           # run tenant migrations
php artisan tenants:list              # list tenants
php artisan theme:list                # list themes
php artisan theme:doctor              # themer health check
php artisan modular:list              # list modules
php artisan route:list | findstr blog # check module routes
```
## 9. Add-a-Module Recipe
```1.
php artisan make:module Invoices
```
2. Fix provider namespace to Modules\Invoices\Providers\InvoicesServiceProvider
3. Set provider in modules/Invoices/module.json to the same FQCN
4. Leave boot() empty in the module's service provider
5. Register centrally:
```
\App\Models\Module::create(['name' => 'Invoices', 'version' => '1.0.0']);
```
6. Attach to tenants that should have it:
```
$tenant->modules()->attach('Invoices', ['enabled' => true]);
```
7. Add middleware(['module:Invoices']) to routes/web.php and api.php
8. Tenant migrations → database/migrations/tenant/
9. Run php artisan tenants:migrate

## 10. Verified Endpoints (as of Step 4)
|URL|	Expected|
|---|-----------|
|http://nxtsaasnet.test/	|Central app (uses central theme)|
|http://test1.nxtsaasnet.test/_theme	|{tenant:test1, theme:alpha, active:alpha}|
|http://test2.nxtsaasnet.test/_theme	|{tenant:test2, theme:null, active:central}|
|http://test1.nxtsaasnet.test/blog	|{module:Blog, tenant:test1, tenant_initialized:true}|
|http://test2.nxtsaasnet.test/blog	|Laravel 404|
|http://nxtsaasnet.test/blog	|Laravel 404|

## 11. TODO / Next Steps
□ Step 5 — alizharb/laravel-hooks extensibility layer
□ Step 6 — alizharb/laravel-modular-filament — tenant + central admin panels
□ Step 7 — alizharb/laravel-modular-livewire — reactive UI components
□ Step 8 — PWA manifest, service worker, offline fallback, per-tenant branding
□ Configure queue driver for tenant provisioning (shouldBeQueued(true) in production)
□ Tenant admin UI to manage module enable/disable
□ Billing / subscription layer
□ Domain verification for custom tenant domains
□ Cross-tenant user invitation flow
□ Backup strategy per tenant DB
□ Central admin panel to manage tenants, modules, plans
□ CI/CD pipeline with tenant migration safety checks

## 12. Glossary
|Term|	Meaning|
|----|---------|
|Central|	The main app / DB (nxtsaasnetdb) holding tenants, users, module registry|
|Tenant|	An isolated customer with its own DB (tenant_<id>)|
|Tenant| DB	A clone of the tenant connection, database name swapped at runtime|
|Module|	A feature package in modules/, opt-in per tenant|
|Theme|	A rendering package in themes/, assigned per tenant|
|Team ID|	Spatie's tenant-scoping key; equals the tenant's ID (string)|
|Tenancy Bootstrapped|	Event fired after Stancl has swapped DB, cache, filesystem|


---

Save that as `docs/ARCHITECTURE.md`. Nothing else changes. The app is untouched.

When you're ready to move on, say **"continue"** and I'll start Step 5 (`alizharb/laravel-hooks`) — installing it, wiring the Eloquent bridge, and testing a hook end-to-end in tenant context.


## 6.5 Hooks (Step 5 — COMPLETE)

Package: `alizharb/hookx` v1.x
Note: `alizharb/laravel-hooks` is legacy and does NOT support Laravel 13.
HookX is the maintained successor.

### API used
- `HookManager::getInstance()` — static singleton
- `->on($name, $callback, $priority = 10)` — register hook listener
- `->addFilter($name, $callback, $priority = 10)` — register filter
- `->applyFilters($name, $value, $arguments = [])` — run value through filters
- `->dispatch($name, $arguments = []): HookContext` — fire hook
- `->reset()` — clear all listeners + filters (CRITICAL for tenancy)
- `->setStrictMode(bool)` — throw if no listener found
- `->registerObject($obj)` — attribute-based registration (#[Hook], #[Filter])

### Tenancy wiring (in app/Providers/TenancyServiceProvider.php)

TenancyBootstrapped listener:
  1. Spatie cache flush (per-tenant cache key)
  2. Theme override (config('themer.active'))
  3. HookManager::getInstance()->reset()   ← prevents cross-tenant leaks
  4. Register tenant-scoped hooks based on tenant()->getTenantKey()

RevertedToCentralContext listener:
  1. Spatie cache restore
  2. Theme restore to central
  3. HookManager::getInstance()->reset()   ← ensures central context is clean

### Why reset matters
HookManager is a static singleton — it survives across requests in the
same PHP process. Under FPM the process ends per request and this is
cosmetic; under Octane, RoadRunner, or queue workers it is mandatory.

### Verified
- test1.nxtsaasnet.test/blog/hook-test → {"filtered":"ALPHA: default",...}
- test2.nxtsaasnet.test/blog/hook-test → {"filtered":"default",...}

### Demo hook
- Filter name: `tenant.display_name`
- Registered when: TenancyBootstrapped fires and tenant key === 'test1'
- Effect: prefixes value with "ALPHA: "

Replace the demo with real registration (config-driven per-tenant hooks
loaded from `tenants.data` JSON or a `tenant_hooks` table) as modules
begin dispatching real events.

### Add-a-Module (updated for Step 6)

1. `php artisan make:module Invoices`
2. Fix provider namespace/class in `module.json` and the provider file
3. Leave the provider's `boot()` empty
4. Register centrally:
   `\App\Models\Module::create(['name' => 'Invoices', 'version' => '1.0.0'])`
5. Attach to tenants:
   `$tenant->modules()->attach('Invoices', ['enabled' => true])`
6. Routes: add `middleware(['module:Invoices'])` to `routes/web.php` and `routes/api.php`
7. Filament resources:
   `php artisan make:filament-resource Invoice --module=Invoices`
   → then extend `App\Filament\TenantResource` instead of `Filament\Resources\Resource`
8. Tenant migrations → `database/migrations/tenant/`
9. `php artisan tenants:migrate`
10. `php artisan optimize:clear`

## 6.6 Filament Panels (Step 6 — COMPLETE)

### Panels

| Panel | Domain | Path | Guard | Auth Model |
|-------|--------|------|-------|------------|
| central | nxtsaasnet.test | /admin | `central` | `App\Models\User` |
| tenant | any tenant domain | /admin | `tenant` | `App\Models\TenantUser` |

### Panel Providers
- app/Providers/Filament/CentralPanelProvider.php
- app/Providers/Filament/TenantPanelProvider.php

### Central Panel Configuration
- No `->domain()` — removed due to Laravel 13 + Filament v5 route collision bug
- Domain restriction enforced by `PreventAccessFromCentralDomains` on tenant panel

### Tenant Panel Configuration
- No domain restriction
- Middleware includes:
  - `InitializeTenancyByDomain::class`
  - `PreventAccessFromCentralDomains::class`
  - with `isPersistent: true` (critical for Livewire AJAX)

### Auth Guards (config/auth.php)
- `central` → `central_users` provider → `App\Models\User` (uses `central` connection)
- `tenant` → `tenant_users` provider → `App\Models\TenantUser` (uses active tenant connection)

### Module Resource Gating
All module Filament resources MUST extend `App\Filament\TenantResource`:

```php
abstract class TenantResource extends Resource
{
    public static function canAccess(): bool
    {
        if (! tenancy()->initialized) return false;
        if (preg_match('#^Modules\\\\([^\\\\]+)\\\\#', static::class, $m)) {
            return tenant()->hasModule($m[1]);
        }
        return false;
    }
}


**Add after the Livewire bridge section:**

```markdown
## 6.7 Livewire Modular Bridge (Step 7 — COMPLETE)

Package: `alizharb/laravel-modular-livewire`

### Behavior
- Auto-discovers components in `Modules/*/app/Livewire` and `Modules/*/resources/views/livewire`
- Registers them with `<module>::` prefix: `<livewire:blog::status />`
- Supports `php artisan make:livewire <name> --module=Blog`
- Livewire 4 full-page routing: `Route::livewire('/blog', 'blog::pages::post.index')`

### Tenant-aware layout
Module Livewire pages use `#[Layout('blog::layouts.app')]` (module layout)
or `#[Layout('layouts.app')]` (theme layout via Themer cascade).

### Theme asset resolution
Module layouts reference theme assets via:

```blade
@vite(
    ['resources/assets/css/app.css', 'resources/assets/js/app.js'],
    'themes/' . config('themer.active')
)



**Add to the "TODO / Next Steps" section:**

```markdown
- [ ] **Step 8** — Per-tenant theme availability (allowlist) + tenant theme selector page
- [ ] **Step 9** — PWA layer (manifest, service worker, per-tenant branding)
- [ ] **Step 10** — CMS module (content types, pages, blocks, frontend rendering)
- [ ] **Step 11** — CMS theme integration (CMS frontend renders through active theme)
- [ ] Restrict `modular-filament` discovery to tenant panel only (currently registers into both)
- [ ] Tenant module admin UI (enable/disable per tenant via central admin)


## 6.10 PWA Layer (Step 9 — COMPLETE)

### Strategy
- **Per-tenant install** — each tenant is its own installable app
- **Static offline fallback** at /offline
- **Theme.json branding** — manifest data comes from the active theme

### Routes (inside routes/tenant.php tenancy group)
- `GET /manifest.webmanifest` → dynamic manifest from active theme.json
- `GET /service-worker.js` → SW with tenant-aware scope
- `GET /offline` → offline fallback page

### theme.json `pwa` block
Located in each theme's theme.json under the top-level `pwa` key:

```json
"pwa": {
    "name": "...",
    "short_name": "...",
    "description": "...",
    "theme_color": "#059669",
    "background_color": "#ffffff",
    "display": "standalone",
    "orientation": "portrait",
    "start_url": "/blog",
    "scope": "/",
    "icons": [
        { "src": "/themes/<name>/pwa/icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/themes/<name>/pwa/icon-512.png", "sizes": "512x512", "type": "image/png" },
        { "src": "/themes/<name>/pwa/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
    ]
}

Critical: JSON syntax. A missing comma after the closing brace of pwa breaks the entire file, causing json_decode() to return null. This was the root cause of one Step 9 debugging session.

Service Worker behavior
Caches /offline on install

Network-first for GET requests

Skips /livewire/* and /admin/* — Livewire POSTs and admin panel routes must not be intercepted

Scope: / (full tenant domain)

Layout integration
Module layout (modules/Blog/resources/views/layouts/app.blade.php) includes:

<link rel="manifest" href="/manifest.webmanifest">

<meta name="theme-color"> (computed from active theme)

Apple touch icon

Service worker registration script

Assets
Theme assets are published via php artisan theme:publish <name>:

public/themes/<name>/css/app.css

public/themes/<name>/js/app.js

public/themes/<name>/pwa/icon-*.png

Layout references them via theme_asset('css/app.css') and theme_asset('js/app.js').

Dev environment requirement
Service Workers require HTTPS or localhost. Chrome silently removes navigator.serviceWorker on insecure origins. In Laragon:

Enable HTTPS in the Laragon menu (recommended)

Or use chrome://flags/#unsafely-treat-insecure-origin-as-secure with tenant domains listed

Verified
PWA installs successfully on https://test1.nxtsaasnet.test/blog

Standalone app opens with tenant-branded icon and name ("NxtSaaSNet Alpha")

Manifest reflects active theme dynamically

Switching theme updates manifest on next reload

Why NOT use a PWA package
Packages like foxws/laravel-pwa, erag/laravel-pwa, and ladumor/laravel-pwa assume a single global manifest. Our per-tenant, theme-driven architecture requires dynamic manifest generation per request, which the packages do not support. The custom implementation is more correct for this stack.

text

Then update the TODO list:

```markdown
## 11. TODO / Next Steps

- [x] **Step 8** — Per-tenant theme availability + tenant theme selector
- [x] **Step 9** — PWA layer (per-tenant install, static offline, theme-driven branding)
- [ ] **Step 10** — CMS module (content types, pages, blocks, frontend rendering)
- [ ] **Step 11** — CMS theme integration
- [ ] Restrict modular-filament discovery to tenant panel only
- [ ] Tenant module admin UI (enable/disable per tenant from central admin)
- [ ] Offline fallback through active theme (currently static blade)
- [ ] Background sync for offline form submissions
- [ ] Per-tenant app icons (currently sourced from theme.json)
- [ ] Billing / subscription layer
- [ ] API authentication (Sanctum) for PWA
- [ ] Queue strategy for tenant provisioning in production
- [ ] Backup / restore strategy per tenant DB


## 7. CMS Module Roadmap

### Architectural model: Model C (Hybrid)

Fixed structural tables with flexible JSON payloads for tenant-specific fields.

### Staged delivery

| Phase | Scope | Status |
|-------|-------|--------|
| 10a | Content types + entries CRUD | next |
| 10b | Public rendering (`/{slug}`) | planned |
| 10c | Typed blocks (hero, text, image, cta, gallery) | planned |
| 10d | Media library (tenant-scoped) | planned |
| 10e | Menus + navigation | planned |
| 10f | SEO + metadata + sitemap | planned |
| 11 | VvvebJs drag-and-drop page builder (optional layer) | planned |

### Why VvvebJs is deferred to Step 11

VvvebJs stores pages as a single HTML blob. Model C stores pages as typed
blocks. These are different rendering models that shouldn't be mixed from
day one. Model C first gives us:

- Typed content (blog posts, products, events) with real DB columns
- Predictable Blade rendering
- Theme integration via Themer's view cascade
- PWA compatibility (public pages are server-rendered, not JS-rendered)

Step 11 adds VvvebJs as an **optional alternative editor** for tenants who
want pixel-level control on landing pages. Both modes coexist:

- `entries.mode = 'structured'` → renders through block system
- `entries.mode = 'builder'` → renders raw HTML from VvvebJs

### VvvebJs integration notes (for Step 11)

- **Storage:** `entries.html` (longtext) column
- **Load:** `Vvveb.Builder.init()` + `Vvveb.Builder.setHtml($entry->html)`
- **Save:** POST endpoint on the tenant panel, writes to `$entry->html`
- **Media:** custom upload endpoint wired to tenant storage
- **Theme:** VvvebJs expects Bootstrap 5; the tenant's active theme provides it
- **Multi-tenant:** route-level tenancy middleware auto-scopes everything
- **PWA:** VvvebJs lives under `/admin/*`, which the SW already excludes

## 11. TODO / Next Steps

- [x] Step 8 — Per-tenant theme availability
- [x] Step 9 — PWA layer
- [ ] **Step 10a** — CMS: content types + entries
- [ ] Step 10b — CMS: public rendering
- [ ] Step 10c — CMS: typed blocks
- [ ] Step 10d — CMS: media library
- [ ] Step 10e — CMS: menus + navigation
- [ ] Step 10f — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration
- [ ] Restrict modular-filament discovery to tenant panel only
- [ ] Tenant module admin UI (central admin)
- [ ] Offline fallback through active theme
- [ ] Billing / subscription layer
- [ ] API authentication (Sanctum) for PWA
- [ ] Queue strategy for tenant provisioning
- [ ] Backup / restore strategy per tenant DB


## 8. CMS Module (Step 10)

### Architectural model: Model C (Hybrid)
Fixed structural tables + flexible JSON payloads for tenant-specific fields.

### Phase 10a — COMPLETE

#### Module
- `modules/Cms/` — registered centrally in `modules` table
- Attached per tenant via `tenant_module` pivot
- `TenantResource::canAccess()` auto-resolves module name "Cms" from namespace

#### Tenant DB schema
- `content_types` — slug, name, description, icon, fields (JSON), settings (JSON), sort_order
- `entries` — content_type_id, slug, title, status, published_at, data (JSON), created_by, updated_by
- Unique: (content_type_id, slug)
- Index: (status, published_at)

#### Models
- `Modules\Cms\Models\ContentType`
- `Modules\Cms\Models\Entry` with scopes: published, draft, forType

#### Filament resources (tenant panel)
- `Modules\Cms\Filament\Resources\ContentTypeResource` — CRUD for content types
- `Modules\Cms\Filament\Resources\EntryResource` — CRUD for entries
- Both extend `App\Filament\TenantResource`
- Auto-discovered by `alizharb/laravel-modular-filament`

#### Dynamic custom fields
`EntryResource::form()` reads the selected content type's `fields` JSON and
renders matching Filament components. Supported field types:

| Type | Filament component |
|------|-------------------|
| text | TextInput |
| textarea | Textarea |
| richtext | RichEditor |
| number | TextInput (numeric) |
| boolean | Toggle |
| date | DatePicker |
| select | Select (options from field config) |

Field values stored in `entries.data` JSON keyed by field `key`.

#### Filament v5 API notes (learned during build)
- Form signature: `public static function form(Schema $schema): Schema`
- Layout components (Section, Grid, Tabs) live in `Filament\Schemas\Components\*`
- Form inputs (TextInput, Select, Repeater) live in `Filament\Forms\Components\*`
- Reactive closures use `Filament\Schemas\Components\Utilities\{Get,Set}`
- `$navigationGroup` type is `UnitEnum|string|null`, not `?string`
- Pages array entries use `Pages\ListX::route('/')`, not `::getRouteName()`

#### Seed data (idempotent migration)
- Content type `page` with fields: body (richtext), featured_image (text)
- Content type `post` with fields: excerpt (textarea), body (richtext), author (text)
- Entry `home` (page)
- Entry `hello-world` (post)

### Phase 10b — planned
Public rendering: `/{slug}` renders a published entry through the active theme.

### Phase 10c–10f — planned
Blocks, media library, menus, SEO.

### Step 11 — planned
VvvebJs drag-and-drop page builder as an optional per-entry editor.

- [x] Step 10a — CMS: content types + entries
- [ ] **Step 10b** — CMS: public rendering (`/{slug}`)
- [ ] Step 10c — CMS: typed blocks
- [ ] Step 10d — CMS: media library
- [ ] Step 10e — CMS: menus + navigation
- [ ] Step 10f — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration

### Phase 10b — COMPLETE

#### Public rendering
- Livewire component: `cms::pages.entry.show`
  - File: `modules/Cms/resources/views/livewire/pages/⚡entry/show.blade.php`
  - Layout: `cms::layouts.app`
- Route: `Route::livewire('/{slug}', 'cms::pages.entry.show')`
  - Name: `cms.entry.show`
  - Constraint: `slug` matches `[a-z0-9\-]+`
  - Loaded via the module glob loop in `routes/tenant.php`

#### Route ordering rule
Fixed tenant routes (`/offline`, `/manifest.webmanifest`, etc.) register before
the module glob loop. Module routes register last. This prevents catch-all
module routes from shadowing fixed routes. Filament's `/admin/*` routes register
during app boot (before `routes/tenant.php`), so they're always matched first.

#### HTML rendering
Entry `data.body` is emitted with `{!! !!}` (raw). Sanitization deferred to
Step 10f.

#### Verified
- test1.nxtsaasnet.test/home renders seeded Home page
- test1.nxtsaasnet.test/hello-world renders seeded post
- test2.nxtsaasnet.test/home renders test2's own Home
- /admin/*, /blog, /offline still work (not shadowed)
- Unknown slug → 404

- [x] Step 10a — CMS: content types + entries
- [x] Step 10b — CMS: public rendering
- [ ] **Step 10c** — CMS: typed blocks (hero, text, image, cta, gallery)

### Phase 10c — COMPLETE

#### Schema
- `entries.blocks` (JSON) — ordered array of `{ type, data }` blocks

Block structure:
```json
{ "type": "hero", "data": { "heading": "...", "subheading": "...", "cta_label": "...", "cta_url": "..." } }

#### Block registry
- `Modules\Cms\Blocks\BlockRegistry` — singleton, registered in `CmsServiceProvider`
- Each block: `{ label, icon, schema (class), view (Blade namespace) }`

#### Built-in blocks
| Type | Schema | View |
|------|--------|------|
| hero | HeroSchema | cms::blocks.hero |
| text | TextSchema | cms::blocks.text |
| image | ImageSchema | cms::blocks.image |
| cta | CtaSchema | cms::blocks.cta |
| gallery | GallerySchema | cms::blocks.gallery |

#### Adding a new block
1. Create schema class in `modules/Cms/app/Blocks/Schemas/`
2. Create Blade partial in `modules/Cms/resources/views/blocks/`
3. Register in `CmsServiceProvider::register()` via `$registry->register(...)`

No changes needed to EntryResource or the public renderer — both are
registry-driven.

#### Editor
- Filament Repeater with dynamic schema per `type`
- Collapsible, cloneable, reorderable
- Block labels come from registry

#### Public rendering
- `cms::pages.entry.show` iterates `$entry->blocks`
- Resolves each block through `BlockRegistry`
- Includes the block's Blade view with `$data`
- Falls back to `entry.data.body` if no blocks exist

- [x] Step 10a — CMS: content types + entries
- [x] Step 10b — CMS: public rendering
- [x] Step 10c — CMS: typed blocks
- [ ] **Step 10d** — CMS: media library
- [ ] Step 10e — CMS: menus + navigation
- [ ] Step 10f — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration


### Phase 10d — COMPLETE

#### Schema
- `media` table (tenant DB): filename, path, mime_type, size, alt, title, uploaded_by, softDeletes
- Index: mime_type

#### Storage
- Disk: `tenant` (config/filesystems.php)
  - root: storage_path('app/public')
  - url: /tenancy/assets
  - visibility: public
- Stancl FilesystemTenancyBootstrapper suffixes storage_path() per tenant
- Actual path: storage/tenant_test1/app/public/uploads/<file>
- Served via GET /tenancy/assets/{path} route (tenancy middleware group)

#### Critical: asset_helper_tenancy must be FALSE
`asset_helper_tenancy => false` in config/tenancy.php. If true, Filament's
framework CSS/JS also get rewritten to /tenancy/assets/, which 404s.
Setting false lets Filament assets load normally while `tenant_asset()`
still works for tenant-specific files.

#### Model
- `Modules\Cms\Models\Media`
- `getUrlAttribute()` returns `tenant_asset($this->path)`
- NOTE: In tinker, tenant_asset() resolves to APP_URL host. Inside real
  HTTP requests, it correctly uses the current tenant domain.

#### Filament resource
- `Modules\Cms\Filament\Resources\MediaResource`
- `FileUpload::make('path')->disk('tenant')->directory('uploads')`
- ImageColumn uses `->getStateUsing(fn ($r) => tenant_asset($r->path))`

#### MediaPicker custom field
- `Modules\Cms\Filament\Components\MediaPicker`
- View: modules/Cms/resources/views/filament/media-picker.blade.php
- **Critical**: uses Alpine `x-on:click` + `$wire.set()`, NOT Livewire `wire:click`
  - Livewire v4 partial boundaries break wire:click inside Filament modals
  - Alpine attaches handlers directly to elements; $wire.set() updates state
    and clears Filament validation errors

#### Block integration
- `ImageSchema` and `GallerySchema` use MediaPicker for `data.media_id`
- Block views use `media_url($mediaId)` helper (defined in app/Support/helpers.php)

#### Verified
- Upload lands in tenant-scoped path
- Thumbnails render in list, edit, and picker
- Selection saves to entry blocks
- /home renders selected image through media_url()

- [x] Step 10a — CMS: content types + entries
- [x] Step 10b — CMS: public rendering
- [x] Step 10c — CMS: typed blocks
- [x] Step 10d — CMS: media library
- [ ] **Step 10e** — CMS: menus + navigation
- [ ] Step 10f — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration

### Phase 10e — COMPLETE

#### Schema
- `menus` table (tenant DB): name, slug (unique), location, items (JSON tree)
- Item structure: { type, label, slug?, url?, target, children?: [...] }
- Max 2 levels of nesting (top-level + sub-items)

#### Model
- `Modules\Cms\Models\Menu`
- `Menu::forLocation(string $location): ?self`
- Items cast to array

#### Helper
- `cms_resolve_menu_url(array $item): string` — resolves entry slugs or URLs

#### Blade components
- `<x-cms::menu location="header" />` — renders a menu
- `<x-cms::menu-item :item="$item" />` — recursive item renderer
- Located at modules/Cms/resources/views/components/

#### Filament resource
- `MenuResource` — CRUD with nested Repeater for sub-items
- Entry picker uses published entries' slugs
- Type switch (entry vs URL) with conditional fields

#### Themes
Themes render menus in their layouts:
```blade
<x-cms::menu location="header" />
<x-cms::menu location="footer" />


Update TODO list:

```markdown
- [x] Step 10e — CMS: menus + navigation
- [ ] **Step 10f** — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration

### Phase 10e — COMPLETE

#### Schema
- `menus` table (tenant DB): name, slug (unique), location, items (JSON tree)
- Item structure: { type, label, slug?, url?, target, children?: [...] }
- Max 2 levels of nesting

#### Model
- `Modules\Cms\Models\Menu`
- `Menu::forLocation(string $location): ?self`

#### Helper
- `cms_resolve_menu_url(array $item): string`

#### Blade components
- `<x-cms::menu location="header" />`
- `<x-cms::menu-item :item="$item" />`
- Located at modules/Cms/resources/views/components/

#### Filament resource
- MenuResource — CRUD with nested Repeater

#### Verified
- Menu renders in tenant layouts
- Sub-items render recursively
- Entry picker uses published entries

- [x] Step 10e — CMS: menus + navigation
- [ ] **Step 10f** — CMS: SEO + sitemap
- [ ] Step 11 — VvvebJs page builder integration

### Phase 10f — COMPLETE

#### Schema
- `entries` gains: seo_title, seo_description, og_image_id, canonical_url, no_index

#### Sanitization
- Package: mews/purifier
- Profile: `richtext` (config/purifier.php)
- Custom cast: `Modules\Cms\Casts\SanitizedJson` on `data`
- Block text bodies sanitized via `setBlocksAttribute` mutator

#### SEO
- Rendered in `cms::layouts.app` from entry SEO fields
- Falls back to entry title for SEO title
- Open Graph + Twitter cards
- `no_index` toggles robots meta

#### Sitemap
- Route: `GET /sitemap.xml` (tenant group, BEFORE module glob loop)
- No cache (database/file cache drivers don't support tenant tags)
- Consider Redis-based caching when traffic justifies it
- XML-escaped URLs via htmlspecialchars(..., ENT_XML1)

#### Robots.txt
- Route: `GET /robots.txt` (tenant group, BEFORE module glob loop)
- Disallows /admin and /livewire, references sitemap

#### Route ordering rule (critical, learned across 10b/10f)
Fixed routes MUST register BEFORE the module glob loop in routes/tenant.php:
  1. Core routes (/, /_theme, /admin/theme/activate)
  2. PWA routes (/manifest.webmanifest, /service-worker.js, /offline)
  3. Asset routes (/tenancy/assets/{path})
  4. SEO routes (/sitemap.xml, /robots.txt)
  5. Module glob loop (loads CMS /{slug}, Blog routes, etc.) LAST

Additionally, the CMS /{slug} route uses a negative lookahead constraint:
  ->where('slug', '^(?!sitemap\.xml|robots\.txt|offline|manifest\.webmanifest|...).*$')
This is the safety net even if the ordering is correct.

#### Verified
- /sitemap.xml returns valid XML
- /robots.txt returns directives with sitemap reference
- SEO meta tags render per entry
- Rich text sanitized on save
- /home and /hello-world still render entries

- [x] Step 10a — CMS: content types + entries
- [x] Step 10b — CMS: public rendering
- [x] Step 10c — CMS: typed blocks
- [x] Step 10d — CMS: media library
- [x] Step 10e — CMS: menus + navigation
- [x] Step 10f — CMS: SEO + sitemap
- [ ] **Step 11** — VvvebJs page builder integration