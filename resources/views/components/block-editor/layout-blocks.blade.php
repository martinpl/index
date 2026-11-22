@props(['blocks'])

{{-- Blocks of the layout shown around the edited ones, faded and inert, so their links cannot leave the editor. --}}
@foreach ($blocks as $block)
    @php($html = $block->toHtml())
    @if ($html !== '')
        <div wire:key="layout-{{ $block->id }}" class="opacity-60" inert>
            <div data-canvas>{!! $html !!}</div>
        </div>
    @endif
@endforeach
