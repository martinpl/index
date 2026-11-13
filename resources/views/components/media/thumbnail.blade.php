@props(['media', 'class' => null])

@php
    $icons = ['image' => 'lucide-file-image', 'video' => 'lucide-file-video', 'audio' => 'lucide-file-audio', 'document' => 'lucide-file-text'];
@endphp

@if ($media->isImage())
    <img
        src="{{ $media->url() }}"
        alt="{{ $media->alt() }}"
        loading="lazy"
        {{ $attributes->twMerge(['bg-muted shrink-0 rounded-md object-cover', $class]) }}
    />
@else
    <span {{ $attributes->twMerge(['bg-muted text-muted-foreground flex shrink-0 items-center justify-center rounded-md', $class]) }}>
        <x-dynamic-component :component="$icons[$media->fileType()]" class="size-1/2 max-h-12 max-w-12" />
    </span>
@endif
