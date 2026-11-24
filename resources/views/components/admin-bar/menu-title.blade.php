@props(['icon' => null])

{{-- The icon and title of a top-level admin bar item, showing only the icon on small screens, so the items fit. --}}
@if ($icon)
    <x-dynamic-component :component="$icon" />
    @if ($slot->isNotEmpty())
        <span class="max-md:sr-only">{{ $slot }}</span>
    @endif
@else
    {{ $slot }}
@endif
