<?php

use Modules\Cms\Models\Media;

if (! function_exists('media_url')) {
    function media_url(int|string|null $id, string $fallback = ''): string
    {
        if (! $id) return $fallback;

        static $cache = [];

        if (! isset($cache[$id])) {
            $cache[$id] = Media::find($id)?->url ?? $fallback;
        }

        return $cache[$id];
    }
}

if (! function_exists('cms_resolve_menu_url')) {
    function cms_resolve_menu_url(array $item): string
    {
        $type = $item['type'] ?? 'url';

        return match ($type) {
            'entry' => $item['slug'] ? url('/' . ltrim($item['slug'], '/')) : '#',
            'url' => $item['url'] ?? '#',
            default => '#',
        };
    }
}