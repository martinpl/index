@if ($media)
    <figure {{ $attributes }}>
        <img
            src="{{ $media->url() }}"
            alt="{{ $alt }}"
            @if ($media->width() && $media->height()) width="{{ $media->width() }}" height="{{ $media->height() }}"@endif
        />
        @if ($caption)
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
