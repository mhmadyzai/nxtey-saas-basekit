<section class="cms-block cms-block-gallery">
    <div class="cms-gallery">
        @foreach(($data['images'] ?? []) as $image)
            <figure>
                <img
                    src="{{ media_url($image['media_id'] ?? null) }}"
                    alt="{{ $image['alt'] ?? '' }}"
                >
            </figure>
        @endforeach
    </div>
</section>