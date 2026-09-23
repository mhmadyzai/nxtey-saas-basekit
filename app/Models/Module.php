<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $connection = 'central';
    protected $table = 'modules';

    protected $fillable = ['name', 'display_name', 'version', 'enabled_globally'];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_module',
            'module',
            'tenant_id',
            'name',
            'id'
        )->withPivot(['enabled', 'settings'])->withTimestamps();
    }
}