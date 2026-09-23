## Themes (Step 4 — COMPLETE)

### Approach
Option A: central app is itself a theme named `central`.
Every rendering context (central or tenant) resolves through the themer.

### Layout
themes/
  central/         # central app + fallback for tenants with no theme
    theme.json
    ThemeServiceProvider.php
    resources/views/
    resources/assets/
    vite.config.js
  alpha/           # tenant theme for test1
    ...

### Config
- config/themer.php
  - themes_path: base_path('themes')
  - active: env('THEME', 'default')   ← runtime key is `themer.active`
  - discovery.scan_modules: true      ← module themes auto-discovered
  - assets.symlink: env('THEMER_SYMLINK', true)

### .env
THEME=central
THEMER_SYMLINK=false   # Windows: avoid admin-privilege symlink failures

### Central DB
- tenants.theme (string, nullable)
  - null → falls back to `THEME` env (central)

### Runtime Switching
In app/Providers/TenancyServiceProvider.php:

TenancyBootstrapped listener:
  - config(['themer.active' => $event->tenancy->tenant->theme])  // if not null

RevertedToCentralContext listener:
  - config(['themer.active' => env('THEME', 'central')])

### Verification Endpoint
GET /_theme (inside routes/tenant.php tenant group) returns:
  { tenant, tenant_theme, themer_active }

### Verified
- test1.nxtsaasnet.test/_theme → tenant=test1, theme=alpha
- test2.nxtsaasnet.test/_theme → tenant=test2, theme=null, active=central
- nxtsaasnet.test/_theme        → 404 (central domain, tenancy prevented)