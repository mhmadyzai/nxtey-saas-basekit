@props(['item'])

@php
    $url = cms_resolve_menu_url($item);
    $label = $item['label'] ?? 'Untitled';
    $children = $item['children'] ?? [];
    $target = ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
    $hasChildren = ! empty($children);
@endphp

<li @class(['cms-menu-item']) style="position: relative;">
    <a href="{{ $url }}" target="{{ $target }}" style="text-decoration: none; color: inherit; padding: 0.5rem 0;">
        {{ $label }}
    </a>

    @if($hasChildren)
        <ul class="cms-menu-sublist" style="list-style: none; padding: 0; margin: 0.5rem 0 0 0; padding-left: 1rem;">
            @foreach($children as $child)
                <x-cms::menu-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>