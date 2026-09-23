<figure class="cms-block cms-block-image">
    @if(!empty($data['media_id']))
        <img
            src="{{ media_url($data['media_id']) }}"
            alt="{{ $data['alt'] ?? '' }}"
        >
    @endif
    @if(!empty($data['caption']))
        <figcaption>{{ $data['caption'] }}</figcaption>
    @endif
</figure>