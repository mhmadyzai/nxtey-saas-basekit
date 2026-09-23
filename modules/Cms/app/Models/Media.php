<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'filename', 'path', 'mime_type', 'size',
        'alt', 'title', 'uploaded_by',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
	{
		return tenant_asset($this->path);
	}

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}