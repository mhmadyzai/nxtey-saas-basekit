<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'name', 'slug', 'location', 'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    /**
     * Return the menu items for a given location.
     * Only published entries are included; external links pass through.
     */
    public static function forLocation(string $location): ?self
    {
        return static::where('location', $location)->first();
    }
}