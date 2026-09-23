<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Theme extends Model
{
    protected $connection = 'central';
    protected $table = 'themes';

    protected $fillable = [
        'name', 'display_name', 'version', 'parent',
        'removable', 'disableable', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'removable' => 'boolean',
        'disableable' => 'boolean',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_theme',
            'theme',
            'tenant_id',
            'name',
            'id'
        )->withPivot('enabled')->withTimestamps();
    }
}