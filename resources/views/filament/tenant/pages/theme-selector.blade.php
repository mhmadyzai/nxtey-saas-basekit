<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        @forelse($this->availableThemes as $theme)
            <div wire:ignore wire:key="theme-{{ $theme->name }}" @class([
                'rounded-xl border p-4',
                'border-primary-500 ring-2 ring-primary-500' => tenant()->theme === $theme->name,
            ])>
                <h3 class="text-lg font-bold">{{ $theme->display_name ?? $theme->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">v{{ $theme->version }}</p>

                @if(tenant()->theme === $theme->name)
                    <span class="mt-4 inline-block font-semibold text-primary-600">
                        ✓ Active
                    </span>
                @else
                    <a href="{{ route('tenant.theme.activate', $theme->name) }}"
   class="mt-4 inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
    Activate
</a>
                @endif
            </div>
        @empty
            <div class="col-span-3 rounded-xl border border-dashed p-8 text-center text-gray-500">
                No themes available for this tenant.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>