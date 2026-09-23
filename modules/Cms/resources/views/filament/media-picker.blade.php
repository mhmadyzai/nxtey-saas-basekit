<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            open: false,
            select(id) {
                $wire.set('{{ $getStatePath() }}', id);
                this.open = false;
            },
            clear() {
                $wire.set('{{ $getStatePath() }}', null);
            }
        }"
        class="space-y-2"
    >
        @php $media = $getMedia(); @endphp

        <div class="flex items-center gap-3">
            @if($media)
                <img src="{{ $media->url }}" class="h-16 w-16 rounded object-cover" alt="">
                <div class="flex-1">
                    <div class="text-sm font-medium">{{ $media->filename }}</div>
                    <div class="text-xs text-gray-500">{{ number_format($media->size / 1024, 1) }} KB</div>
                </div>
                <button type="button" x-on:click="clear()" class="text-sm text-red-600">Remove</button>
                <button type="button" x-on:click="open = true" class="text-sm text-indigo-600">Change</button>
            @else
                <button
                    type="button"
                    x-on:click="open = true"
                    class="rounded-lg border border-dashed px-4 py-2 text-sm text-gray-600 hover:border-indigo-500"
                >
                    Choose Media
                </button>
            @endif
        </div>

        <div
            x-show="open"
            x-cloak
            @click.self="open = false"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="max-h-[80vh] w-full max-w-4xl overflow-auto rounded-xl bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Media Library</h3>
                    <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-800">✕</button>
                </div>

                @php $mediaItems = \Modules\Cms\Models\Media::latest()->get(); @endphp

                @if($mediaItems->isEmpty())
                    <p class="text-gray-500">No media uploaded yet.</p>
                @else
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($mediaItems as $m)
                            <button
                                type="button"
                                x-on:click="select({{ $m->id }})"
                                class="rounded-lg border p-2 text-left hover:border-indigo-500 hover:bg-gray-50"
                            >
                                <img
                                    src="{{ $m->url }}"
                                    alt="{{ $m->alt ?? '' }}"
                                    style="width: 100%; height: 120px; object-fit: cover;"
                                    class="mb-1 rounded"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >
                                <div style="display: none; padding: 1rem; background: #f3f4f6; text-align: center; border-radius: 0.25rem;">
                                    Preview unavailable
                                </div>
                                <div class="truncate text-xs">{{ $m->filename }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>