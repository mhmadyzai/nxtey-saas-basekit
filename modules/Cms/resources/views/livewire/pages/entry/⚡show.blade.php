<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Cms\Blocks\BlockRegistry;
use Modules\Cms\Models\Entry;

new #[Layout('cms::layouts.app')] class extends Component
{
    public ?Entry $entry = null;

    public function mount(string $slug): void
    {
        $this->entry = Entry::query()
            ->with('contentType')
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();
    }

    public function with(): array
    {
        return [
            'tenant' => tenant()?->getTenantKey(),
            'theme' => config('themer.active'),
			'blocks' => $this->resolveBlocks(),
        ];
    }
	
	protected function resolveBlocks(): array
    {
        $registry = app(BlockRegistry::class);
        $resolved = [];

        foreach (($this->entry->blocks ?? []) as $block) {
            $type = $block['type'] ?? null;
            if (! $type || ! $registry->has($type)) {
                continue;
            }

            $resolved[] = [
                'type' => $type,
                'view' => $registry->get($type)['view'],
                'data' => $block['data'] ?? [],
            ];
        }

        return $resolved;
    }
};
?>

<article class="cms-entry">
    <header>
        <h1>{{ $entry->title }}</h1>
        <p><small>{{ $entry->contentType->name }} · {{ $entry->published_at?->format('M j, Y') }}</small></p>
    </header>

    @if(!empty($blocks))
        @foreach($blocks as $block)
            @include($block['view'], ['data' => $block['data']])
        @endforeach
    @else
        {{-- Fallback to legacy body field --}}
        <div>
            {!! $entry->data['body'] ?? '' !!}
        </div>
    @endif
</article>