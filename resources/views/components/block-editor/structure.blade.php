@props(['blocks', 'blockTypes', 'selectedBlock' => null])

@php
    use Illuminate\Support\Str;

    $selectedIndex = array_search($selectedBlock, array_column($blocks, 'id'), true);
@endphp

<nav {{ $attributes->twMerge('flex flex-col gap-2') }} aria-label="{{ __('Structure') }}">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-sm font-medium">{{ __('Structure') }}</h2>
        <x-block-editor.inserter
            :$blockTypes
            :position="$selectedIndex === false ? null : $selectedIndex + 1"
            align="start"
        >
            <x-ui.button size="icon" class="size-7" :aria-label="__('Add block')">
                <x-lucide-plus />
            </x-ui.button>
        </x-block-editor.inserter>
    </div>

    @if (empty($blocks))
        <p class="text-muted-foreground text-sm">{{ __('No blocks yet.') }}</p>
    @endif

    <ol wire:sort="moveBlock" class="flex flex-col gap-0.5">
        @foreach ($blocks as $index => $block)
            @php($blockType = $blockTypes[$block['type']] ?? null)
            <li wire:key="structure-{{ $block['id'] }}" wire:sort:item="{{ $block['id'] }}">
                <button
                    type="button"
                    wire:click="selectBlock(@js($block['id']))"
                    @if ($block['id'] === $selectedBlock) aria-current="true" @endif
                    @class([
                        'hover:bg-accent flex w-full cursor-grab items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm active:cursor-grabbing',
                        'bg-accent text-accent-foreground font-medium' => $block['id'] === $selectedBlock,
                    ])
                >
                    <x-dynamic-component
                        :component="$blockType['icon'] ?? 'lucide-circle-help'"
                        class="text-muted-foreground size-4 shrink-0"
                    />
                    <span class="truncate">{{ __($blockType['name'] ?? Str::headline($block['type'])) }}</span>
                    @if ($block['attributes']['id'] ?? null)
                        <span class="text-muted-foreground truncate text-xs">#{{ $block['attributes']['id'] }}</span>
                    @endif
                    @if ($errors->has("blocks.$index.*"))
                        <span
                            class="bg-destructive ms-auto size-2 shrink-0 rounded-full"
                            title="{{ __('Invalid') }}"
                        ></span>
                    @endif
                </button>
            </li>
        @endforeach
    </ol>
</nav>
