<?php

namespace Modules\Cms\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Mews\Purifier\Facades\Purifier;

class SanitizedJson implements CastsAttributes
{
    protected array $richTextKeys = ['body', 'excerpt', 'content'];

    public function get($model, string $key, $value, array $attributes)
    {
        return json_decode($value ?? '[]', true) ?: [];
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_array($value)) {
            $value = $this->sanitizeRecursive($value);
        }

        return json_encode($value);
    }

        protected function sanitizeRecursive(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $this->sanitizeRecursive($v);
            } elseif (is_string($v) && in_array($k, $this->richTextKeys, true)) {
                // ➡️ Strip loading attribute variations (loading="lazy", loading='eager', etc.)
                $v = preg_replace('/\s+loading=["\']?(lazy|eager)["\']?/i', '', $v);

                $data[$k] = Purifier::clean($v, 'richtext');
            }
        }

        return $data;
    }
}