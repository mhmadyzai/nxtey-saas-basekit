<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# NxtSaaSNet — SaaS Base Kit

Multi-tenant SaaS base built on Laravel 13, Stancl Tenancy, Filament 5, and Livewire 4.

**Documentation:** See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

**Stack:**
- Laravel 13 / PHP 8.5
- stancl/tenancy (database-per-tenant)
- spatie/laravel-permission (teams)
- alizharb/laravel-modular (opt-in modules)
- alizharb/laravel-themer (per-tenant themes)
- alizharb/hookx (hooks)
- Filament 5 (dual panels: central + tenant)
- Livewire 4 (public pages)

**Local setup:**
1. `composer install && npm install`
2. Copy `.env.example` to `.env`, configure DB
3. `php artisan key:generate`
4. `php artisan migrate`
5. Create a tenant (see docs/ARCHITECTURE.md)
