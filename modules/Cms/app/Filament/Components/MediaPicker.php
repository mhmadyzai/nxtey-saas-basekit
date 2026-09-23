<?php

namespace Modules\Cms\Filament\Components;

use Filament\Forms\Components\Field;
use Modules\Cms\Models\Media;

class MediaPicker extends Field
{
    protected string $view = 'cms::filament.media-picker';

    public bool $multiple = false;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function getMedia()
    {
        if ($this->multiple) {
            return Media::whereIn('id', (array) $this->getState())->get();
        }

        return Media::find($this->getState());
    }
}