<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    protected $table = 'content_types';

    protected $fillable = [
        'slug', 'name', 'description', 'icon', 'fields', 'settings', 'sort_order',
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}