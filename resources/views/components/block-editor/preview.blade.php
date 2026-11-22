@props(['blocks', 'blockTypes', 'selectedBlock' => null, 'metaboxes' => []])

@php
    use App\Entries\Layout;
    use App\Facades\BlockType;
    use Illuminate\Support\Str;

    [$before, $contentBlock, $after] = Layout::chosenIn($metaboxes)?->aroundContent() ?? [[], null, []];
@endphp

<div {{ $attributes->twMerge('canvas z-10') }}>
    <x-block-editor.layout-blocks :blocks="$before" />

    <div {{ $contentBlock?->htmlAttributes() }}>
        @forelse ($blocks as $block)
            @php
                $blockType = $blockTypes[$block['type']] ?? null;
                $instance = $blockType ? BlockType::make($block) : null;
                $html = $instance?->toHtml() ?? '';
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
                    'min-h-8 cursor-pointer outline-offset-4 hover:outline hover:outline-1 focus-visible:outline-2',
                    'outline-primary outline outline-2 hover:outline-2' => $block['id'] === $selectedBlock,
                ])
            >
                @if ($html !== '')
                    <div data-canvas>{!! $html !!}</div>
                @else
                    <div class="text-muted-foreground flex items-center gap-2 rounded-md border border-dashed px-4 py-3 text-sm">
                        <x-dynamic-component
                            :component="$blockType['icon'] ?? 'lucide-circle-help'"
                            class="size-4 shrink-0"
                        />
                        @if ($blockType)
                            {{ $instance->placeholder() ?? __(':block block — set it up in the block settings.', ['block' => $name]) }}
                        @else
                            {{ __('The :type block type is not available, so the block is kept as it is but not shown on the site.', ['type' => $block['type']]) }}
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center gap-3 p-8 text-center">
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

    <x-block-editor.layout-blocks :blocks="$after" />
</div>
