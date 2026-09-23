@props(['location' => 'header', 'class' => ''])

@php
    $menu = \Modules\Cms\Models\Menu::forLocation($location);
    $items = $menu?->items ?? [];
@endphp

@if(!empty($items))
    <nav class="cms-menu" aria-label="{{ $menu->name }}">
    <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 1rem;">
        @foreach($items as $item)
            <x-cms::menu-item :item="$item" />
        @endforeach
    </ul>
</nav>
@endif