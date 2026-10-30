@props(['blocks', 'blockTypes', 'selectedBlock' => null])

@php
    use App\Facades\BlockType;
    use Illuminate\Support\Str;
@endphp

<div {{ $attributes->twMerge('block-preview') }}>
    @forelse ($blocks as $block)
        @php
            $blockType = $blockTypes[$block['type']] ?? null;
            $html = $blockType ? BlockType::make($block)->toHtml() : '';
            $name = __($blockType['name'] ?? Str::headline($block['type']));
        @endphp
        <div
            wire:key="preview-{{ $block['id'] }}"
            wire:click.prevent="selectBlock(@js($block['id']))"
            x-on:keydown.enter.prevent="$wire.selectBlock(@js($block['id']))"
            role="button"
            tabindex="0"
            aria-label="{{ $name }}"
            aria-pressed="{{ $block['id'] === $selectedBlock ? 'true' : 'false' }}"
            @class([
                'min-h-8 cursor-pointer rounded-sm outline-offset-4 hover:outline hover:outline-1 focus-visible:outline-2',
                'outline-primary outline outline-2 hover:outline-2' => $block['id'] === $selectedBlock,
            ])
        >
            @if ($html !== '')
                {!! $html !!}
            @else
                <div class="text-muted-foreground flex items-center gap-2 rounded-md border border-dashed px-4 py-3 text-sm">
                    <x-dynamic-component
                        :component="$blockType['icon'] ?? 'lucide-circle-help'"
                        class="size-4 shrink-0"
                    />
                    @if ($blockType)
                        {{ __(':block block — set it up in the block settings.', ['block' => $name]) }}
                    @else
                        {{ __('The :type block type is not available, so the block is kept as it is but not shown on the site.', ['type' => $block['type']]) }}
                    @endif
                </div>
            @endif
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed p-8 text-center">
            <p class="text-muted-foreground text-sm">{{ __('Start writing by adding a block.') }}</p>
            <x-block-editor.inserter :$blockTypes align="center">
                <x-ui.button variant="outline" size="sm">
                    <x-lucide-square-plus />
                    {{ __('Add block') }}
                </x-ui.button>
            </x-block-editor.inserter>
        </div>
    @endforelse
</div>
