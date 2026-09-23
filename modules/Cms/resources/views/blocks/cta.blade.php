<section class="cms-block cms-block-cta">
    @if(!empty($data['heading']))
        <h2>{{ $data['heading'] }}</h2>
    @endif
    @if(!empty($data['body']))
        <p>{{ $data['body'] }}</p>
    @endif
    @if(!empty($data['button_label']) && !empty($data['button_url']))
        <a href="{{ $data['button_url'] }}" class="cms-cta">{{ $data['button_label'] }}</a>
    @endif
</section>