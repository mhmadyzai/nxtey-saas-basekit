<?php
// Module routes are loaded here (not in module service providers) so that
// Stancl tenancy middleware and per-tenant module gating always apply.
// Each module's route file must declare `middleware(['module:ModuleName'])`
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Cms\Models\Entry;

// Web routes
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', fn () => 'This is your multi-tenant application.');
	
	Route::get('/_theme', function () {
    return response()->json([
        'tenant' => tenant()?->getTenantKey(),
        'tenant_theme' => tenant()?->theme,
        'themer_active' => config('themer.active'),
    ]);
});
	Route::get('/admin/theme/activate/{theme}', function (string $theme) {
        if (! tenant()->hasTheme($theme)) {
            abort(404);
        }

        tenant()->update(['theme' => $theme]);

        return redirect()->back();
    })->name('tenant.theme.activate');

    Route::get('/manifest.webmanifest', function () {
    $theme = config('themer.active');
    $themePath = config('themer.themes_path') . '/' . $theme . '/theme.json';

    if (! file_exists($themePath)) {
        abort(404);
    }

    $themeData = json_decode(file_get_contents($themePath), true);
    $pwa = $themeData['pwa'] ?? [];

    return response()->json([
        'name' => $pwa['name'] ?? $themeData['name'] ?? config('app.name'),
        'short_name' => $pwa['short_name'] ?? $themeData['name'] ?? config('app.name'),
        'description' => $pwa['description'] ?? '',
        'start_url' => $pwa['start_url'] ?? '/',
        'scope' => $pwa['scope'] ?? '/',
        'display' => $pwa['display'] ?? 'standalone',
        'orientation' => $pwa['orientation'] ?? 'portrait',
        'theme_color' => $pwa['theme_color'] ?? '#000000',
        'background_color' => $pwa['background_color'] ?? '#ffffff',
        'icons' => $pwa['icons'] ?? [],
        'tenant' => tenant()?->getTenantKey(),
    ], 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('tenant.pwa.manifest');

Route::get('/service-worker.js', function () {
    $content = <<<'JS'
const CACHE_NAME = 'tenant-pwa-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    if (event.request.url.includes('/livewire')) return;
    if (event.request.url.includes('/admin')) return;

    event.respondWith(
        fetch(event.request).catch(() =>
            caches.match(event.request).then((cached) => cached || caches.match(OFFLINE_URL))
        )
    );
});
JS;

    return response($content, 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
    ]);
})->name('tenant.pwa.sw');

Route::get('/offline', function () {
    return view('offline');
})->name('tenant.pwa.offline');

Route::get('/tenancy/assets/{path}', function (string $path) {
    $disk = Storage::disk('tenant');

    if (! $disk->exists($path)) {
        abort(404);
    }

    return $disk->response($path);
})->where('path', '.*')->name('tenant.assets');

Route::get('/sitemap.xml', function () {
    $cacheKey = 'sitemap:' . tenant()->getTenantKey();

    $xml = Cache::remember($cacheKey, 3600, function () {
        $entries = Entry::query()
            ->published()
            ->where('no_index', false)
            ->orderBy('updated_at', 'desc')
            ->get();

        $urls = '';
        foreach ($entries as $entry) {
            $loc = url('/' . ltrim($entry->slug, '/'));
            $lastmod = $entry->updated_at->toAtomString();
            $urls .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod></url>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
            $urls .
            '</urlset>';
    });

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('tenant.sitemap');

Route::get('/robots.txt', function () {
    $sitemapUrl = url('/sitemap.xml');

    $content = "User-agent: *\n";
    $content .= "Allow: /\n";
    $content .= "Disallow: /admin\n";
    $content .= "Disallow: /livewire\n";
    $content .= "\nSitemap: {$sitemapUrl}\n";

    return response($content, 200, ['Content-Type' => 'text/plain']);
})->name('tenant.robots');
	
	foreach (glob(base_path('modules/*/routes/web.php')) as $file) {
        require $file;
    }
});

// API routes
Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api')->group(function () {
    foreach (glob(base_path('modules/*/routes/api.php')) as $file) {
        require $file;
    }
});