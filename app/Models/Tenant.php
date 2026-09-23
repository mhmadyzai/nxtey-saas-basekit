<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Theme;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'tenant_module',
            'tenant_id',
            'module',
            'id',       // tenants.id
            'name'      // modules.name
        )->withPivot(['enabled', 'settings'])->withTimestamps();
    }

    public function hasModule(string $module): bool
	{
		return $this->modules()
			->wherePivot('enabled', true)
			->where('modules.name', $module)
			->exists();
	}
	
	public function themes(): BelongsToMany
	{
		return $this->belongsToMany(
			Theme::class,
			'tenant_theme',
			'tenant_id',
			'theme',
			'id',
			'name'
		)->withPivot('enabled')->withTimestamps();
	}

	public function hasTheme(string $theme): bool
	{
		return $this->themes()
			->wherePivot('enabled', true)
			->where('themes.name', $theme)
			->exists();
	}
}