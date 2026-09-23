<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache; // ➡️ ADDED FOR THE SITEMAP CACHE CLEAR ON BOOTED
use Mews\Purifier\Facades\Purifier; // ➡️ ADDED FOR PURIFIER RESOLUTION

class Entry extends Model
{
    protected $table = 'entries';

    protected $fillable = [
        'content_type_id', 'slug', 'title', 'status',
        'published_at', 'data', 'blocks', 'created_by', 'updated_by',
		'seo_title', 'seo_description', 'og_image_id', 'canonical_url', 'no_index',
    ];

    protected $casts = [
        'data' => \Modules\Cms\Casts\SanitizedJson::class,
		'blocks' => 'array',
        'published_at' => 'datetime',
		'no_index' => 'boolean',
    ];

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
                 ->where(function (Builder $q) {
                     $q->whereNull('published_at')
                       ->orWhere('published_at', '<=', now());
                 });
    }

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', 'draft');
    }

    public function scopeForType(Builder $q, string $typeSlug): Builder
    {
        return $q->whereHas('contentType', fn (Builder $q) => $q->where('slug', $typeSlug));
    }
	
	public function ogImage()
	{
		return $this->belongsTo(Media::class, 'og_image_id');
	}
	
	public function setBlocksAttribute($value): void
	{
		if (is_array($value)) {
			foreach ($value as &$block) {
				if (($block['type'] ?? null) === 'text' && isset($block['data']['body'])) {
                    // ➡️ Strip loading attribute values to prevent HTMLPurifier structure crashes
                    $bodyString = preg_replace('/\s+loading=["\']?(lazy|eager)["\']?/i', '', $block['data']['body']);
                    
					$block['data']['body'] = Purifier::clean($bodyString, 'richtext');
				}
			}
		}

		$this->attributes['blocks'] = json_encode($value);
	}
	
		protected static function booted(): void
		{
			static::saved(function () {
				try {
					\Illuminate\Support\Facades\Cache::forget('sitemap:' . tenant()?->getTenantKey());
				} catch (\BadMethodCallException $e) {
					// Fallback if the underlying tenant cache manager fails on driver tags
					\Illuminate\Support\Facades\Cache::store()->forget('sitemap:' . tenant()?->getTenantKey());
				}
			});

			static::deleted(function () {
				try {
					\Illuminate\Support\Facades\Cache::forget('sitemap:' . tenant()?->getTenantKey());
				} catch (\BadMethodCallException $e) {
					\Illuminate\Support\Facades\Cache::store()->forget('sitemap:' . tenant()?->getTenantKey());
				}
			});
		}

}
