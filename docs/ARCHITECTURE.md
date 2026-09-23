# NxtSaaSNet — Architecture Reference

**Last updated:** after Step 10f (CMS SEO + Sitemap) — Steps 1–10 complete.
**Stack:** Laravel 13, PHP 8.5, MySQL (Laragon on Windows), Livewire 4, Filament 5.8.
**Purpose:** Multi-tenant SaaS platform with database-per-tenant isolation,
opt-in modules, per-tenant themes, a structured CMS, and PWA support.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Stack Inventory](#2-stack-inventory)
3. [Repository Layout](#3-repository-layout)
4. [Database Topology](#4-database-topology)
5. [Step 1 — Multi-Tenancy](#5-step-1--multi-tenancy)
6. [Step 2 — Users & Permissions](#6-step-2--users--permissions)
7. [Step 3 — Modules](#7-step-3--modules)
8. [Step 4 — Themes](#8-step-4--themes)
9. [Step 5 — Hooks (HookX)](#9-step-5--hooks-hookx)
10. [Step 6 — Filament Panels](#10-step-6--filament-panels)
11. [Step 7 — Livewire Bridge](#11-step-7--livewire-bridge)
12. [Step 8 — Per-Tenant Theme Availability](#12-step-8--per-tenant-theme-availability)
13. [Step 9 — PWA Layer](#13-step-9--pwa-layer)
14. [Step 10 — CMS Module](#14-step-10--cms-module)
15. [Cross-Cutting Patterns](#15-cross-cutting-patterns)
16. [Known Issues & Workarounds](#16-known-issues--workarounds)
17. [Deferred Decisions](#17-deferred-decisions)
18. [Add-a-Module Recipe](#18-add-a-module-recipe)
19. [Add-a-Theme Recipe](#19-add-a-theme-recipe)
20. [Verification Checklist](#20-verification-checklist)
21. [TODO / Roadmap](#21-todo--roadmap)
22. [Glossary](#22-glossary)

---

## 1. Overview

NxtSaaSNet is a multi-tenant SaaS platform built as an API-first, PWA-ready,
modular CMS. Each tenant is fully isolated at the database level, has its own
theme, its own content, its own permissions, and its own installable PWA.

**Core principles:**

- **Isolation first** — every tenant's data lives in a separate database
- **Opt-in modules** — tenants only get the modules they're entitled to
- **Per-tenant theming** — tenants pick from admin-curated themes
- **Structured content** — content is typed (content types + blocks), not freeform HTML
- **Extensibility via hooks** — modules interact through HookX, not direct coupling
- **Two admin surfaces** — central (platform) and tenant (customer)

**Domain convention:**

- Central: `nxtsaasnet.test`
- Tenants: `<tenant>.nxtsaasnet.test`

---

## 2. Stack Inventory

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | 13.x | Core framework |
| php | 8.5 | Runtime |
| livewire/livewire | 4.x | Reactive components |
| filament/filament | 5.8.x | Admin panels |
| stancl/tenancy | 3.x | Database-per-tenant isolation |
| spatie/laravel-permission | Latest | Roles + permissions with teams |
| alizharb/laravel-modular | Latest | Module system |
| alizharb/laravel-modular-filament | 1.1.x | Filament discovery in modules |
| alizharb/laravel-modular-livewire | 1.1.x | Livewire discovery in modules |
| alizharb/laravel-themer | 1.4.x | Theme system |
| alizharb/hookx | 1.1.x | Hook/filter system (Laravel 13 compatible) |
| alizharb/filament-modular-luncher | Latest | Admin UI for modules |
| alizharb/filament-themer-luncher | Latest | Admin UI for themes |
| mews/purifier | Latest | HTML sanitization |

**Rejected packages:**
- `alizharb/laravel-hooks` — superseded by HookX (no Laravel 13 support)
- `foxws/laravel-pwa`, `erag/laravel-pwa`, `ladumor/laravel-pwa` — single-tenant only, incompatible with per-tenant theme-driven manifests

**Deferred packages:**
- VvvebJs — see [Deferred Decisions](#17-deferred-decisions)

---

## 3. Repository Layout
app/
├── Filament/
│ ├── TenantResource.php # Base class: gates module resources by namespace
│ ├── Central/
│ │ ├── Pages/
│ │ │ ├── Dashboard.php # Custom route path (Laravel 13 fix)
│ │ │ └── Auth/LoginResponse.php
│ │ └── Resources/
│ │ └── TenantThemeAvailabilities/
│ └── Tenant/
│ └── Pages/
│ └── ThemeSelector.php # Tenant picks their theme
├── Http/
│ └── Middleware/
│ ├── EnsureTenantHasModule.php
│ └── InitializeTenancyByDomain.php # Central-domain skip wrapper
├── Models/
│ ├── User.php # Central identity
│ ├── Tenant.php # Stancl tenant + modules/themes relations
│ ├── TenantUser.php # Per-tenant mirror user
│ ├── Module.php # Central module registry
│ └── Theme.php # Central theme registry
├── Providers/
│ ├── AppServiceProvider.php # FilePreviewController middleware
│ ├── TenancyServiceProvider.php # All tenancy events
│ └── Filament/
│ ├── CentralPanelProvider.php
│ └── TenantPanelProvider.php
├── Services/
│ └── TenantTeamResolver.php # Spatie team resolver
├── Support/
│ └── helpers.php # media_url(), cms_resolve_menu_url()
└── Tenancy/
└── Bootstrappers/
└── CustomFilesystemTenancyBootstrapper.php # (currently unused, kept for reference)

config/
├── app.php # Middleware groups (universal, aliases)
├── auth.php # web / central / tenant guards
├── database.php # central + tenant + media connections
├── filesystems.php # tenant disk
├── livewire.php # temporary_file_upload config
├── modular.php # routes => false (manual loading)
├── permission.php # teams + custom resolver
├── purifier.php # richtext profile
├── tenancy.php # bootstrappers, features, disks
└── themer.php # themes_path, active, discovery

database/
├── migrations/ # CENTRAL migrations
│ ├── ..._create_tenants_table.php
│ ├── ..._create_domains_table.php
│ ├── ..._create_users_table.php
│ ├── ..._create_tenant_user_table.php
│ ├── ..._create_modules_table.php
│ ├── ..._create_tenant_module_table.php
│ ├── ..._create_themes_table.php
│ ├── ..._create_tenant_theme_table.php
│ └── ..._add_theme_to_tenants_table.php
└── migrations/tenant/ # TENANT migrations (run per tenant)
├── ..._create_users_table.php
├── ..._create_permission_tables.php
├── ..._create_posts_table.php
├── ..._create_content_types_table.php
├── ..._create_entries_table.php
├── ..._add_blocks_to_entries_table.php
├── ..._add_seo_to_entries_table.php
├── ..._create_media_table.php
├── ..._create_menus_table.php
└── ..._seed_cms_demo_data.php

modules/
├── Blog/
│ ├── module.json
│ ├── app/Providers/BlogServiceProvider.php
│ └── routes/{web,api}.php
└── Cms/
├── module.json
├── app/
│ ├── Blocks/
│ │ ├── BlockRegistry.php
│ │ └── Schemas/{Hero,Text,Image,Cta,Gallery}Schema.php
│ ├── Casts/SanitizedJson.php
│ ├── Filament/
│ │ ├── Components/MediaPicker.php
│ │ └── Resources/{ContentType,Entry,Media,Menu}Resource.php
│ ├── Models/{ContentType,Entry,Media,Menu}.php
│ └── Providers/CmsServiceProvider.php
├── resources/views/
│ ├── blocks/{hero,text,image,cta,gallery}.blade.php
│ ├── components/{menu,menu-item}.blade.php
│ ├── filament/media-picker.blade.php
│ ├── layouts/app.blade.php
│ └── livewire/pages/⚡entry/show.blade.php
└── routes/web.php

themes/
├── central/
│ ├── theme.json # Includes pwa block
│ ├── vite.config.js
│ └── resources/{views,assets}/
└── alpha/
├── theme.json
├── vite.config.js
└── resources/{views,assets}/

routes/
├── web.php # Central routes
├── console.php
└── tenant.php # ALL tenant routes (fixed + module glob)

docs/
└── ARCHITECTURE.md # This file

---

## 4. Database Topology

### Central DB (`nxtsaasnetdb`)

| Table | Purpose |
|-------|---------|
| tenants | Tenant records (id, theme, data JSON) |
| domains | Domain → tenant mapping |
| users | Cross-tenant identities |
| tenant_user | Pivot: which users belong to which tenants |
| modules | Global module registry |
| tenant_module | Pivot: which tenants have which modules enabled |
| themes | Global theme registry (synced from disk) |
| tenant_theme | Pivot: which themes each tenant can select |

### Tenant DBs (`tenant_<id>`)

Created on `TenantCreated` event. Each tenant DB contains:

| Table | Purpose |
|-------|---------|
| users | Per-tenant mirror (FK target for roles) |
| sessions | Tenant-scoped sessions |
| cache, cache_locks | If CACHE_STORE=database |
| roles, permissions, model_has_roles, model_has_permissions, role_has_permissions | Spatie permissions (team_id is STRING) |
| posts | Blog module |
| content_types | CMS content type definitions |
| entries | CMS content |
| media | CMS media library |
| menus | CMS navigation |

**Isolation rule:** no cross-tenant queries. Central tables are never joined to tenant tables except via tenant_user pivot in central context.

---

## 5. Step 1 — Multi-Tenancy

**Package:** `stancl/tenancy`

### Connections

`config/database.php`:

```php
'central' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'database' => env('DB_DATABASE', 'nxtsaasnetdb'),
    // ...
],

'tenant' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'database' => null,           // swapped by Stancl
    // ...
],```

### Config (config/tenancy.php)
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
    'template_tenant_connection' => null,   // falls back to `tenant`
    'prefix' => 'tenant_',
    'suffix' => '',
],

'migration_parameters' => [
    '--force' => true,
    '--path' => [database_path('migrations/tenant')],
    '--realpath' => true,
],

'filesystem' => [
    'disks' => ['tenant'],
    'root_override' => [
        'tenant' => '%storage_path%/app/public',
    ],
    'suffix_storage_path' => true,
    'asset_helper_tenancy' => false,   // CRITICAL: must be false
],

'bootstrappers' => [
    Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,  // default, not custom
    Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
],
```
### Tenant Model
app/Models/Tenant.php:
```
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function modules(): BelongsToMany { /* tenant_module */ }
    public function themes(): BelongsToMany  { /* tenant_theme */ }

    public function hasModule(string $module): bool
    {
        return $this->modules()
            ->wherePivot('enabled', true)
            ->where('modules.name', $module)
            ->exists();
    }

    public function hasTheme(string $theme): bool
    {
        return $this->themes()
            ->wherePivot('enabled', true)
            ->where('themes.name', $theme)
            ->exists();
    }
}
```
### Provisioning
TenancyServiceProvider::events():

TenantCreated → JobPipeline: CreateDatabase, MigrateDatabase

TenantDeleted → DeleteDatabase

Both jobs run synchronously (shouldBeQueued(false)). For production, move to queued jobs.

Verification
```
tenancy()->initialize(App\Models\Tenant::find('test1'));
DB::connection()->getDatabaseName();  // "tenant_test1"
tenancy()->end();
DB::connection()->getDatabaseName();  // "nxtsaasnetdb"
```
### 6. Step 2 — Users & Permissions
Package: spatie/laravel-permission with teams enabled.

### Design: Option C (Hybrid)
Central users — one identity per person, in central DB

Central tenant_user pivot — user belongs to N tenants

Tenant users — per-tenant mirror in each tenant DB (FK target)

Roles/permissions — per-tenant DB, scoped by team_id

### Config (config/permission.php)
```
'teams' => true,

'column_names' => [
    'team_foreign_key' => 'team_id',
],

'team_resolver' => \App\Services\TenantTeamResolver::class,

'cache' => [
    'store' => 'array',   // prevents cross-tenant cache leak
],
```
#### Critical:
team_id is a STRING, not unsignedBigInteger. The permission migration is customized:
```
$table->string($columnNames['team_foreign_key'])->nullable();  // in roles
$table->string($columnNames['team_foreign_key']);              // in pivots
```
UUID tenant IDs fit naturally in a VARCHAR(255) or VARCHAR(36) column.

### TenantTeamResolver
app/Services/TenantTeamResolver.php:
```
public function getPermissionsTeamId(): int|string|null
{
    if ($this->teamId === null && tenancy()->initialized) {
        return tenant()->getTenantKey();
    }
    return $this->teamId;
}
```
When tenancy is active, hasRole(), assignRole(), can() all scope to the current tenant automatically.

### Guards (config/auth.php)
```
'guards' => [
    'web'     => ['driver' => 'session', 'provider' => 'users'],
    'central' => ['driver' => 'session', 'provider' => 'central_users'],
    'tenant'  => ['driver' => 'session', 'provider' => 'tenant_users'],
],

'providers' => [
    'users'         => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'central_users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'tenant_users'  => ['driver' => 'eloquent', 'model' => App\Models\TenantUser::class],
],
```
Critical: TenantUser must declare protected $guard_name = 'web'; explicitly. Without it, Spatie cannot match the guard on assignRole().
### 7. Step 3 — Modules
Package: alizharb/laravel-modular

### Design: Opt-In Per Tenant
Module code lives in modules/*/ — one install, all tenants

Module registry — central modules table

Tenant activation — central tenant_module pivot with enabled flag

Routes — gated by module:<Name> middleware

Migrations — always in database/migrations/tenant/, run per tenant

### Config (config/modular.php)
```
'discovery' => [
    'configs' => true,
    'views' => true,
    'translations' => true,
    'migrations' => true,
    'routes' => false,       // CRITICAL: manual route loading
    'blade_components' => true,
],
```
### Route Loading (routes/tenant.php)
Module routes are loaded inside the tenant route group via glob:
```
foreach (glob(base_path('modules/*/routes/web.php')) as $file) {
    require $file;
}
```
Each module's route file must include middleware(['module:<Name>']).

### Middleware
app/Http/Middleware/EnsureTenantHasModule.php:
```
public function handle(Request $request, Closure $next, string $module): Response
{
    if (! tenancy()->initialized) abort(404);
    if (! tenant()->hasModule($module)) abort(404);
    return $next($request);
}
```
Registered as alias module in bootstrap/app.php.

### Module Provider Rules
Namespace: Modules\<Name>\Providers

Empty boot() — do NOT load routes here (bypasses tenancy middleware)

module.json provider FQCN must match the file's namespace

### 8. Step 4 — Themes
Package: alizharb/laravel-themer

### Design: Option A (central is itself a theme)
themes/central/ — central app + fallback for tenants with no theme

themes/alpha/ — example tenant theme

Active theme stored in tenants.theme (central DB)

Runtime override via config('themer.active')

### Config (config/themer.php)
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
### .env
```
THEME=central
THEMER_SYMLINK=false   # Windows: no symlink privilege needed
```
### Runtime Switching
In TenancyServiceProvider::events(), TenancyBootstrapped:
```
if ($event->tenancy->tenant->theme) {
    config(['themer.active' => $event->tenancy->tenant->theme]);
}
```
RevertedToCentralContext:
```
config(['themer.active' => env('THEME', 'central')]);
```
### Theme Builds
Each theme has its own vite.config.js and package.json. Root package.json declares:
```
"workspaces": ["themes/*"]
```
#### Build with:
```
php artisan theme:build --theme=alpha
php artisan theme:build --theme=central
```
Output: public/themes/<name>/build/manifest.json and public/themes/<name>/build/assets/*.

### Layout Asset References
In theme-aware layouts:
```
<link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
<script src="{{ theme_asset('js/app.js') }}" defer></script>
```
The theme_asset() helper resolves against the active theme.

### 9. Step 5 — Hooks (HookX)
Package: alizharb/hookx (Laravel 13 compatible; alizharb/laravel-hooks is legacy)

#### API
```
use AlizHarb\Hookx\HookManager;

$manager = HookManager::getInstance();
$manager->on('hook.name', fn ($ctx) => /* ... */);
$manager->addFilter('filter.name', fn ($value) => /* ... */);
$manager->dispatch('hook.name', ['arg' => 'value']);
$result = $manager->applyFilters('filter.name', $value);
$manager->reset();   // clears all listeners + filters
```
### Tenancy Wiring
TenancyBootstrapped:

HookManager::getInstance()->reset()

Register tenant-scoped hooks based on tenant()->getTenantKey()

RevertedToCentralContext:

HookManager::getInstance()->reset()

Why reset matters: HookManager is a static singleton. Under Octane, queue workers, or any long-lived process, hooks leak between tenants if not reset.

#### Verified
test1.nxtsaasnet.test/blog/hook-test → filtered by tenant-specific hook

test2.nxtsaasnet.test/blog/hook-test → unfiltered

### 10. Step 6 — Filament Panels
Packages: filament/filament v5, alizharb/filament-modular-luncher, alizharb/filament-themer-luncher
#### Panels

Panel	Domain				Path	Guard	Auth Model
central	nxtsaasnet.test		/admin	central	App\Models\User
tenant	any tenant domain	/admin	tenant	App\Models\TenantUser

### Central Panel
app/Providers/Filament/CentralPanelProvider.php

No ->domain() — removed due to Laravel 13 + Filament v5 route collision

Custom Dashboard at app/Filament/Central/Pages/Dashboard.php with $routePath = '/dashboard'

Plugins: ModularLuncherPlugin, ThemerLuncherPlugin

Correct namespaces:
```
use AlizHarb\ModularLuncher\Filament\Plugins\ModularLuncherPlugin;
use AlizHarb\ThemerLuncher\Filament\Plugins\ThemerLuncherPlugin;
```
### Tenant Panel
app/Providers/Filament/TenantPanelProvider.php

No domain restriction (middleware handles it)

Persistent middleware:
```
->middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
], isPersistent: true)
```
### Module Resource Gating
All module Filament resources must extend App\Filament\TenantResource:
```
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
```
The regex reads the module name from the namespace (Modules\Cms\... → Cms). Any resource under App\Filament\... is denied — those are platform-level resources only.

### Livewire Update Route
AppServiceProvider::boot():
```
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

FilePreviewController::$middleware = [
    'web',
    'universal',
    InitializeTenancyByDomain::class,
];
```
TenancyServiceProvider::boot():
```
Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/livewire/update', $handle)
        ->middleware(['web', 'universal', InitializeTenancyByDomain::class]);
});
```
Without these, tenant Livewire AJAX requests fail with session/CSRF errors.

### 11. Step 7 — Livewire Bridge
Package: alizharb/laravel-modular-livewire

#### Behavior
Auto-discovers Livewire components in Modules/*/app/Livewire

Namespace prefix: <livewire:blog::status />

make:livewire <name> --module=Blog scaffolds into the module

Livewire 4 full-page routing: Route::livewire('/blog', 'blog::pages.post.index')

#### Module Layout
Module layouts use #[Layout('blog::layouts.app')] which resolves to modules/Blog/resources/views/layouts/app.blade.php.

Theme Asset References in Module Layouts
```
@vite(
    ['resources/assets/css/app.css', 'resources/assets/js/app.js'],
    'themes/' . config('themer.active') . '/build'
)
```
NOTE: Themer overrides @vite and prepends the theme path. This was a source of bugs. Simpler alternative:
```
<link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
<script src="{{ theme_asset('js/app.js') }}" defer></script>
```
### 12. Step 8 — Per-Tenant Theme Availability
#### Central Tables
themes — registry synced from themes/*/theme.json via php artisan themes:sync

tenant_theme — pivot: which themes each tenant can select

#### Models
App\Models\Theme

App\Models\Tenant::themes(), hasTheme(string)

#### Central Admin
Resource: TenantThemeAvailabilityResource

Path: nxtsaasnet.test/admin/theme-availability

Controls which themes each tenant can choose

#### Tenant Panel
Page: App\Filament\Tenant\Pages\ThemeSelector

Path: <tenant>.nxtsaasnet.test/admin/theme-selector

Lists themes where tenant_theme.enabled = true

#### Activation Pattern
Filament v5 + Livewire v4 has a known issue where wire:click and x-on:click inside Filament partial boundaries do not fire. Use a GET link for theme switching:
```
// routes/tenant.php, inside the tenancy middleware group
Route::get('/admin/theme/activate/{theme}', function (string $theme) {
    if (! tenant()->hasTheme($theme)) abort(404);
    tenant()->update(['theme' => $theme]);
    return redirect()->back();
})->name('tenant.theme.activate');
```
```
<a href="{{ route('tenant.theme.activate', $theme->name) }}">Activate</a>
```

### 13. Step 9 — PWA Layer
#### Strategy
Per-tenant install — each tenant's PWA is branded independently

Static offline fallback at /offline

Branding sourced from theme.json — no DB columns needed

#### Routes (tenant group, before module glob)
```
Route::get('/manifest.webmanifest', /* dynamic manifest */)->name('tenant.pwa.manifest');
Route::get('/service-worker.js', /* SW content */)->name('tenant.pwa.sw');
Route::get('/offline', fn () => view('offline'))->name('tenant.pwa.offline');
```

#### theme.json pwa Block
```
"pwa": {
    "name": "NxtSaaSNet Alpha",
    "short_name": "Alpha",
    "description": "Alpha tenant application",
    "theme_color": "#059669",
    "background_color": "#ffffff",
    "display": "standalone",
    "orientation": "portrait",
    "start_url": "/blog",
    "scope": "/",
    "icons": [
        { "src": "/themes/alpha/pwa/icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/themes/alpha/pwa/icon-512.png", "sizes": "512x512", "type": "image/png" }
    ]
}
```
#### Layout Integration
Tenant layouts include:
```
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="{{ config('themer.active') === 'alpha' ? '#059669' : '#4f46e5' }}">
<link rel="apple-touch-icon" href="/themes/{{ config('themer.active') }}/pwa/icon-192.png">
```
And before </body>:
```
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
        });
    }
</script>
```
#### Service Worker
Skips /livewire/* and /admin/* from caching. Serves /offline on network failure.

#### HTTPS Requirement
Service Workers require HTTPS or localhost. In Laragon, enable HTTPS via the menu. In Chrome, add chrome://flags/#unsafely-treat-insecure-origin-as-secure with *.nxtsaasnet.test.

### 14. Step 10 — CMS Module
#### Model: Model C (Hybrid)
Fixed structural tables + flexible JSON for tenant-defined fields.

#### Phase 10a — Content Types + Entries
Schema (tenant DB):

content_types — slug, name, description, icon, fields (JSON), settings (JSON), sort_order

entries — content_type_id, slug, title, status, published_at, data (JSON), blocks (JSON), SEO columns

#### Models:

Modules\Cms\Models\ContentType

Modules\Cms\Models\Entry with scopes: published, draft, forType

#### Filament Resources:

ContentTypeResource, EntryResource — both extend App\Filament\TenantResource

Dynamic custom fields render based on selected content type's fields JSON

Supported field types: text, textarea, richtext, number, boolean, date, select

#### Phase 10b — Public Rendering
Livewire component cms::pages.entry.show at route /{slug}:
```
Route::livewire('/{slug}', 'cms::pages.entry.show')
    ->where('slug', '^(?!sitemap\.xml|robots\.txt|offline|...).*$');
```
Route ordering: fixed routes register before the module glob loop in routes/tenant.php. See Cross-Cutting Patterns.

#### Phase 10c — Typed Blocks
entries.blocks JSON structure:
```
[
    { "type": "hero", "data": { "heading": "...", "cta_label": "...", "cta_url": "..." } },
    { "type": "text", "data": { "body": "<p>...</p>" } }
]
```
#### Block Registry (Modules\Cms\Blocks\BlockRegistry):

Singleton, populated in CmsServiceProvider::register()

Each block: { label, icon, schema (class), view (Blade namespace) }

#### Built-in blocks:
Type		Schema			View
hero		HeroSchema		cms::blocks.hero
text		TextSchema		cms::blocks.text
image		ImageSchema		cms::blocks.image
cta			CtaSchema		cms::blocks.cta
gallery		GallerySchema	cms::blocks.gallery

#### CRITICAL — Field Name Prefixing: Block schema classes MUST prefix every field with data.:
```
Forms\Components\TextInput::make('data.heading')   // ✅
Forms\Components\TextInput::make('heading')        // ❌ saves as sibling of type
```
Without the prefix, values save as siblings of type instead of nested under data, and the renderer (which reads $block['data']) shows nothing.

Inside nested repeaters, fields stay unprefixed because they're relative to each item.

#### Public Rendering:
```
@foreach($blocks as $block)
    @include($block['view'], ['data' => $block['data']])
@endforeach
```
Falls back to entry.data.body if no blocks exist.

#### Phase 10d — Media Library
Schema (tenant DB):

media — filename, path, mime_type, size, alt, title, uploaded_by, softDeletes

#### Storage:

Disk: tenant (config/filesystems.php)

root: storage_path('app/public')

url: /tenancy/assets

Stancl suffixes storage_path() per tenant → storage/tenant_test1/app/public/uploads/

Served via GET /tenancy/assets/{path} route

#### CRITICAL: asset_helper_tenancy => false in config/tenancy.php. If true, Filament's framework CSS/JS also get rewritten to /tenancy/assets/, which 404s.

Model:
```
public function getUrlAttribute(): string
{
    return tenant_asset($this->path);
}
```
NOTE: In tinker, tenant_asset() resolves to APP_URL host. Inside real HTTP requests, it uses the current tenant domain.

#### Filament Resource:

MediaResource with FileUpload::make('path')->disk('tenant')->directory('uploads')

ImageColumn uses ->getStateUsing(fn ($r) => tenant_asset($r->path))

#### MediaPicker Custom Field:

Modules\Cms\Filament\Components\MediaPicker

Uses Alpine x-on:click + $wire.set(), NOT Livewire wire:click

Livewire v4 partial boundaries break wire:click inside Filament modals

$wire.set() updates state AND clears Filament validation errors

#### Block Integration:

ImageSchema and GallerySchema use MediaPicker for data.media_id

Block views use media_url($mediaId) helper

#### Phase 10e — Menus + Navigation
Schema (tenant DB):

menus — name, slug (unique), location, items (JSON tree)

Item structure:
```
{ "type": "entry|url", "label": "...", "slug": "...", "url": "...", "target": "_self|_blank", "children": [...] }
```
Max 2 levels of nesting.

Model: Modules\Cms\Models\Menu with forLocation(string): ?self

Helper: cms_resolve_menu_url(array $item): string

#### Blade Components:

<x-cms::menu location="header" />

<x-cms::menu-item :item="$item" /> (recursive)

Located at modules/Cms/resources/views/components/

Filament Resource: MenuResource with nested Repeater for sub-items.

Themes render menus:
```
<x-cms::menu location="header" />
<x-cms::menu location="footer" />
```
Themes can override default styling by defining their own cms::components.menu view.

### Phase 10f — SEO + Sitemap
Schema: entries gains: seo_title, seo_description, og_image_id, canonical_url, no_index

#### Sanitization:

Package: mews/purifier

Profile: richtext (config/purifier.php)

Custom cast: Modules\Cms\Casts\SanitizedJson on data

Block text bodies sanitized via setBlocksAttribute mutator

#### SEO Meta Tags:

Rendered in cms::layouts.app from entry SEO fields

Falls back to entry title for SEO title

Open Graph + Twitter cards

no_index toggles robots meta

#### Sitemap:

Route: GET /sitemap.xml (tenant group, BEFORE module glob loop)

No cache — database/file cache drivers don't support tenant tags

XML-escaped URLs via htmlspecialchars(..., ENT_XML1)

Consider Redis-based caching when traffic justifies it

#### Robots.txt:

Route: GET /robots.txt (tenant group, BEFORE module glob loop)

Disallows /admin, /livewire, references sitemap

### 15. Cross-Cutting Patterns
#### Route Ordering Rule
Fixed routes must register BEFORE the module glob loop in routes/tenant.php:

Core routes (/, /_theme, /admin/theme/activate/{theme})

PWA routes (/manifest.webmanifest, /service-worker.js, /offline)

Asset routes (/tenancy/assets/{path})

SEO routes (/sitemap.xml, /robots.txt)

Module glob loop LAST (loads CMS /{slug}, Blog routes, etc.)

Additionally, the CMS /{slug} route uses a negative lookahead constraint:
```
->where('slug', '^(?!sitemap\.xml|robots\.txt|offline|manifest\.webmanifest|service-worker\.js|tenancy|admin|livewire|blog|_theme).*$')
```
This is the safety net — even if ordering is correct, catch-all routes shadow fixed routes in some cases. Add any new root-level route to this list.

#### Storage Pattern
Central files: default local disk (storage/app/...)

Tenant files: tenant disk, suffixed by Stancl's FilesystemTenancyBootstrapper

Serving: GET /tenancy/assets/{path} route reads from Storage::disk('tenant')

URLs in code: tenant_asset($path) or media_url($mediaId)

#### Filament v5 API Notes
Learned during build:

Concept						Filament v5
Form signature				form(Schema $schema): Schema
Layout components			Filament\Schemas\Components\* (Section, Grid, Tabs)
Form inputs					Filament\Forms\Components\* (TextInput, Select, Repeater)
Reactive closures			Filament\Schemas\Components\Utilities\{Get,Set}
Navigation group type		UnitEnum|string|null
Page routing				Pages\ListX::route('/')

#### Sanitization
All rich text fields pass through Purifier::clean($html, 'richtext')

data JSON cast to SanitizedJson (recursive)

Block text bodies sanitized in setBlocksAttribute mutator

#### Livewire v4 Partial Boundaries
Filament wraps admin page bodies in wire:partial boundaries. wire:click and x-on:click sometimes don't fire inside these boundaries. Reliable workaround: use Alpine x-on:click with $wire.methodName() for actions inside Filament content.

#### Tenancy Bootstrapped Events
All tenancy-scoped state must be set in TenancyBootstrapped (fires after DB swap):

Spatie permission cache key

Theme override (config('themer.active'))

### 16. Known Issues & Workarounds
#### Laravel 13 + Filament v5 Dashboard Route Collision
Symptom: /admin returns 404 after login on central panel.

Cause: Laravel 13 route collection uses + (first-write-wins). Filament's home redirect route registers before the Dashboard route, so Dashboard is dropped.

Fix:

Custom Dashboard at app/Filament/Central/Pages/Dashboard.php with $routePath = '/dashboard'

Remove ->domain() from CentralPanelProvider (Laravel 13 + domain restriction triggers the bug)

#### Cache Store Tagging
Symptom: This cache store does not support tagging.

Cause: Stancl's CacheTenancyBootstrapper calls Cache::tags(), which requires Redis/Memcached. database and file stores don't support tags.

Fix: Skip Cache::remember() in tenant context for non-taggable drivers. Sitemap caching removed for this reason. Re-enable when Redis is available.

#### Session / CSRF with Tenancy
Symptom: "This page has expired" (419) on tenant Livewire actions.

Cause: Livewire upload/preview/update routes don't run tenancy middleware, so session reads from central DB.

Fix:

config/livewire.php temporary_file_upload.middleware = ['throttle:60,1', 'universal', InitializeTenancyByDomain::class]

FilePreviewController::$middleware set in AppServiceProvider::boot()

Livewire::setUpdateRoute() with ['web', 'universal', InitializeTenancyByDomain::class]

universal middleware group registered as empty group in bootstrap/app.php

Stancl\Tenancy\Features\UniversalRoutes::class enabled in config/tenancy.php

#### Filament FileUpload Tenant Path
Symptom: Upload spinner never completes, file size never read.

Cause: Filament reads temp file size from a path that doesn't match where Livewire wrote it (tenancy path mismatch).

Fix:

Use tenant disk for FileUpload, not public

Set asset_helper_tenancy => false

Ensure /tenancy/assets/{path} route exists

Use Alpine x-on:click + $wire.set() in custom picker

#### Filament $navigationIcon Type
Symptom: Type of $navigationIcon must be BackedEnum|string|null.

Cause: Filament v5 tightened property type variance.

Fix: Use getter methods instead of property declarations:

HookX reset + tenant hook registration

All must be reverted in RevertedToCentralContext.
```
public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
{
    return 'heroicon-o-swatch';
}
```
Always import Htmlable explicitly: use Illuminate\Contracts\Support\Htmlable;.

#### Module Resource Namespace
Symptom: Class "App\Filament\Tenant\Resources\...\TenantResource" not found or duplicate class redeclaration.

Cause: Filament's make:filament-resource places files in app/Filament/Tenant/Resources/ but the module autoloader expects Modules\<Name>\.

Fix: Always create module resources inside the module at modules/<Name>/app/Filament/Resources/ with namespace Modules\<Name>\Filament\Resources. Do not use the generator's default location for module resources.

#### JSON Trailing Comma in theme.json
Symptom: PWA manifest returns defaults (theme_color: "#000000", empty icons).

Cause: theme.json invalid JSON — missing comma after pwa block.

Fix: Validate JSON before saving. php -r "var_dump(json_decode(file_get_contents('themes/alpha/theme.json')) !== null);" should print bool(true).

### 17. Deferred Decisions
#### VvvebJs Page Builder — REJECTED
Considered: Integrating givanz/VvvebJs as an optional drag-and-drop page builder for freeform landing pages.

Rejected because:

Framework mismatch: VvvebJs is Bootstrap 5; themes are Tailwind. Two CSS frameworks colliding.

Rendering model mismatch: VvvebJs emits HTML strings; everything else is Blade-rendered.

Livewire morph conflict: Livewire v4 would diff DOM manipulated by VvvebJs, causing refresh loops.

Tenancy asset complexity: VvvebJs assets would need tenant-scoped serving.

Iframe sandbox issues: Preview iframe requires session/cookie inheritance from the tenant domain.

Filament integration: Requires bypassing Filament entirely, fragmenting the admin UI.

Theme disconnect: VvvebJs pages wouldn't inherit the tenant's theme tokens, typography, or colors.

Preferred alternative: Extend the structured block system with more block types. If tenants need custom HTML, add a "raw HTML block" with Purifier sanitization. This stays within the stack, inherits the theme, and doesn't introduce a second framework.

If a real need emerges: Revisit as a standalone module (not a step), with a Tailwind-native component set. Do not integrate Bootstrap.

#### Queue Strategy for Tenant Provisioning
CreateDatabase and MigrateDatabase currently run synchronously (shouldBeQueued(false)). For production: set shouldBeQueued(true) and configure a supervisor-managed queue worker. Provisioning takes 3–10 seconds per tenant.

#### Cache Driver
Currently CACHE_STORE=database. This blocks Stancl's cache tagging. For production: switch to Redis. Enables tenant cache scoping and sitemap caching.

#### Storage Backend
tenant disk is local. For production: consider S3 with per-tenant prefixes. The disk abstraction allows a switch without code changes.

#### Per-Tenant Icons
Currently read from theme.json. Alternative: store per-tenant icons in DB (tenants.pwa_icon_192, etc.) so tenants can customize without a theme change. Defer until tenants request it.

### 18. Add-a-Module Recipe
1. Scaffold:
```
php artisan make:module Invoices
```
2. Fix provider metadata:

File: modules/Invoices/app/Providers/InvoicesServiceProvider.php

Namespace: Modules\Invoices\Providers

module.json → "provider": "Modules\\Invoices\\Providers\\InvoicesServiceProvider"

Leave boot() empty — routes load via routes/tenant.php

3. Register centrally:
```
\App\Models\Module::create(['name' => 'Invoices', 'version' => '1.0.0']);
```
4. Attach to tenants:
```
\App\Models\Tenant::find('test1')->modules()->attach('Invoices', ['enabled' => true]);
```
5. Add module gate to routes:
```
// modules/Invoices/routes/web.php
Route::middleware(['web', 'module:Invoices'])->prefix('invoices')->group(function () {
    // routes
});
```
6. Create Filament resources inside the module:
```
php artisan make:filament-resource Invoice --panel=tenant
```
Then move the generated files to modules/Invoices/app/Filament/Resources/ and update namespaces to Modules\Invoices\Filament\Resources.

Make the resource extend App\Filament\TenantResource. Do not define canAccess() — inherited from the base.
7. Tenant migrations:
Put all migrations in database/migrations/tenant/:
```
php artisan make:migration create_invoices_table --path=database/migrations/tenant
php artisan tenants:migrate
```
8.Clear and verify:
```
composer dump-autoload
php artisan optimize:clear
php artisan filament:clear-cached-components
php artisan modular:list
```

### Rules
Every module Filament resource extends App\Filament\TenantResource

Every module route includes middleware(['module:<Name>'])

Never load routes from a module's service provider boot()

Never put central tables in database/migrations/tenant/ and vice versa

### 19. Add-a-Theme Recipe
1. Scaffold:
```
php artisan theme:make mytheme --description="..." --author="..."
```
2. Edit themes/mytheme/theme.json:

name, slug, version, author

pwa block with name, short_name, theme_color, icons

Validate JSON (see Known Issues)
3. Add PWA icons:
```
mkdir themes\mytheme\pwa
```
Place icon-192.png, icon-512.png in themes/mytheme/pwa/.

4. Build assets:
```
php artisan theme:build --theme=mytheme
```
Output: public/themes/mytheme/build/{manifest.json, assets/*}.
5. Sync to registry:
```
php artisan themes:sync
```
6. Make available to tenants:
```
\App\Models\Tenant::find('test1')->themes()->attach('mytheme', ['enabled' => true]);
```
7. Publish:
```
php artisan theme:publish mytheme
```
8. Verify:
Log into tenant panel

Navigate to Appearance

Select mytheme → activate

Reload public pages → theme applies

#### Optional: Parent Cascade
Set "parent": "central" in theme.json so views fall back to central when not overridden.

### 20. Verification Checklist
Run these after any significant change:

Infrastructure
```
php artisan tenants:list
php artisan modular:list
php artisan theme:list
php artisan theme:doctor
php artisan modular:doctor
```
#### Routes
```
php artisan route:list | findstr /I "sitemap robots manifest offline blog home admin"
```
All routes should be listed. No missing tenant routes.

#### Public Endpoints (test1)
URL												Expected
test1.nxtsaasnet.test/							Static message
test1.nxtsaasnet.test/home						CMS entry "Home"
test1.nxtsaasnet.test/hello-world				CMS entry "Hello World"
test1.nxtsaasnet.test/blog						Blog module page
test1.nxtsaasnet.test/blog/hook-test			JSON with tenant filter applied
test1.nxtsaasnet.test/sitemap.xml				Valid XML
test1.nxtsaasnet.test/robots.txt				Robots directives
test1.nxtsaasnet.test/manifest.webmanifest		Per-tenant manifest JSON
test1.nxtsaasnet.test/offline					Offline fallback page
test1.nxtsaasnet.test/nonexistent				Laravel 404

#### Public Endpoints (test2)
URL												Expected
test2.nxtsaasnet.test/home						test2's own Home entry
test2.nxtsaasnet.test/blog						Laravel 404 (Blog not enabled)

#### Admin Panels
URL												Expected
nxtsaasnet.test/admin							Central panel, no CMS content group
test1.nxtsaasnet.test/admin						Tenant panel with Content group
test1.nxtsaasnet.test/admin/entries				Entries list loads
test1.nxtsaasnet.test/admin/entries/{id}/edit	Entry editor with blocks + SEO
test1.nxtsaasnet.test/admin/media				Media list with thumbnails
test1.nxtsaasnet.test/admin/menus				Menus list
test1.nxtsaasnet.test/admin/theme-selector		Appearance page

#### Tenancy Isolation
```
tenancy()->initialize(App\Models\Tenant::find('test1'));
\Modules\Cms\Models\Entry::count();  // test1's entries
tenancy()->end();

tenancy()->initialize(App\Models\Tenant::find('test2'));
\Modules\Cms\Models\Entry::count();  // test2's entries (different)
tenancy()->end();
```

#### PWA Install
Open https://test1.nxtsaasnet.test/blog in Chrome

Install icon appears in address bar

Click Install → standalone app window opens

App shows "NxtSaaSNet Alpha" branding

### 21. TODO / Roadmap
#### Completed
☑ Step 1 — Multi-tenancy (database per tenant)
☑ Step 2 — Users & permissions (Option C, Spatie teams)
☑ Step 3 — Modules (opt-in per tenant)
☑ Step 4 — Themes (per-tenant)
☑ Step 5 — Hooks (HookX)
☑ Step 6 — Filament panels + Lunchers
☑ Step 7 — Livewire modular bridge
☑ Step 8 — Per-tenant theme availability
☑ Step 9 — PWA layer
☑ Step 10a — CMS content types + entries
☑ Step 10b — CMS public rendering
☑ Step 10c — CMS typed blocks
☑ Step 10d — CMS media library
☑ Step 10e — CMS menus + navigation
☑ Step 10f — CMS SEO + sitemap
#### Next Moves (Priority Order)
□ Tenant Admin UI — Module Manager
Page in tenant panel listing modules the tenant has access to

Tenant can enable/disable modules within their allowed set

Requires extending tenant_module with a user_toggleable flag or a new tenant_user_modules pivot for per-user preferences

Estimated: 1 day

□ Central Admin UI — Tenant CRUD
Central panel resource for creating, editing, deleting tenants

Fields: id, name, domain, theme, module enablement

Provisioning queue wired to the UI

Estimated: 1 day

□ Billing / Subscription Layer
Stripe via Laravel Cashier

Plans gate module availability (tenant_module sync on plan change)

Central panel: plan assignment per tenant

Invoices, subscription status, upgrade/downgrade flows

Estimated: 3–5 days

□ API Authentication (Sanctum)
Token issuance for PWA

Guard: sanctum reading from tenant DB

Rate limiting per tenant

Estimated: 1–2 days

□ Queue Strategy for Production
TenantCreated / TenantDeleted set to shouldBeQueued(true)

Supervisor config

Failed job handling + retry

Estimated: half day

□ Redis Cache
Switch CACHE_STORE=redis

Re-enable Stancl cache tagging

Re-enable sitemap caching

Estimated: half day

□ Backup / Restore
Per-tenant DB backup (mysqldump → S3)

Restore from backup UI in central panel

Scheduled backup job

Estimated: 2–3 days

□ Tenant Custom Domains
Allow tenants to add their own domains (not just subdomain)

Domain verification flow (DNS TXT record)

Automatic SSL via Laravel Forge / Let's Encrypt

Estimated: 2–3 days

□ Cross-Tenant User Invitations
A central user can be invited to multiple tenants

Email invitation flow

Accept/decline via signed URL

Estimated: 2 days

□ Tenant-Facing Onboarding Wizard
After first login, guide tenant through setup

Choose theme, create first content type, add menu, publish first page

Estimated: 1–2 days

□ CMS Enhancements
Version history / restore for entries

Scheduled publishing (cron-based)

Draft preview URLs (signed, expiring)

Bulk import/export (JSON)

Estimated: 3–5 days

□ Advanced Module Features
Module dependencies (already in module.json, enforce on enable)

Per-tenant module settings UI

Module-provided dashboard widgets

Estimated: 2–3 days

□ Observability
Laravel Telescope in dev

Structured logging per tenant (log channel with tenant context)

Sentry integration for production errors

Estimated: 1 day

□ CI/CD Pipeline
GitHub Actions: tests, migrations, deployments

Tenant migration safety checks (dry-run)

Estimated: 1–2 days

□ Testing Suite
Feature tests for tenancy isolation

API tests for public endpoints

Filament resource tests

Estimated: 3–5 days

#### Deferred / Rejected
□ ~~VvvebJs page builder~~ — rejected (see Deferred Decisions)
□ Consider Tailwind-native block expansion if tenant demand for freeform layout emerges

### 22. Glossary
Term								Meaning
Central								The main app / DB (nxtsaasnetdb) holding tenants, users, module registry
Tenant								An isolated customer with its own DB (tenant_<id>)
Tenant DB							A clone of the tenant connection with a swapped database name
Module								A feature package in modules/, opt-in per tenant
Theme								A rendering package in themes/, assigned per tenant
Block								A typed content unit inside an entry (hero, text, image, cta, gallery)
Content Type						A tenant-defined schema (page, post, product) with custom fields
Entry								A record of a content type, with blocks + SEO metadata
Team ID								Spatie's tenant-scoping key; equals the tenant's ID (string)
Tenancy Bootstrapped				Event fired after Stancl swaps DB, cache, filesystem
Luncher								Admin UI package for managing modules or themes
Universal Routes					Routes that work in both central and tenant context
Partial Boundary					Livewire 4 feature Filament uses; breaks some click delegation

### End of document.

---

## How to Save This

1. Open `docs/ARCHITECTURE.md` in your editor
2. Delete everything currently in it
3. Paste the entire content above
4. Save
5. Commit to git with a message like `docs: comprehensive architecture reference post-Step-10`

This document is now **the single source of truth**. Any future session, teammate, or debugging effort starts by reading it.

## What's Next

The **Next Moves** section at the bottom of the doc is your roadmap. My recommended order:

1. **Tenant Module Manager** (1 day) — lets tenants enable/disable modules within their allowed set
2. **Central Tenant CRUD** (1 day) — admin UI for creating tenants without tinker
3. **Billing Layer** (3–5 days) — this is what turns the platform into a business
4. **API Auth with Sanctum** (1–2 days) — PWA needs tokens
5. **Queue for Provisioning** (half day) — production readiness

Everything else can follow in any order. None of it requires touching the core architecture — the foundation is stable and the seams are clean.

You've built something real. The doc makes it durable. Take a breath, commit, and pick your next target when you're ready.
