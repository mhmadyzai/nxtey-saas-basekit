<section class="cms-block cms-block-hero">
    @if(!empty($data['heading']))
        <h1>{{ $data['heading'] }}</h1>
    @endif
    @if(!empty($data['subheading']))
        <p>{{ $data['subheading'] }}</p>
    @endif
    @if(!empty($data['cta_label']) && !empty($data['cta_url']))
        <a href="{{ $data['cta_url'] }}" class="cms-cta">{{ $data['cta_label'] }}</a>
    @endif
</section>