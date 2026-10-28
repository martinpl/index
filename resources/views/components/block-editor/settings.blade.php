@props(['blocks', 'blockTypes', 'selectedBlock' => null])

@php
    use App\Facades\BlockType;
    use Illuminate\Support\Str;

    $index = array_search($selectedBlock, array_column($blocks, 'id'), true);
    $block = $index === false ? null : $blocks[$index];
    $blockType = $block ? $blockTypes[$block['type']] ?? null : null;
@endphp

@if (! $block)
    <p class="text-muted-foreground text-sm">{{ __('Select a block to edit its settings.') }}</p>
@else
    <div
        wire:key="settings-{{ $block['id'] }}"
        x-on:input.debounce.500ms="$wire.$refresh()"
        x-on:change="$wire.$refresh()"
        {{ $attributes->twMerge('flex flex-col gap-6') }}
    >
        <div class="flex items-start gap-3">
            <x-dynamic-component
                :component="$blockType['icon'] ?? 'lucide-circle-help'"
                class="text-muted-foreground mt-0.5 size-5 shrink-0"
            />
            <div class="flex flex-col gap-1">
                <h3 class="text-sm font-medium">{{ __($blockType['name'] ?? Str::headline($block['type'])) }}</h3>
                @if ($blockType['description'] ?? null)
                    <p class="text-muted-foreground text-xs">{{ __($blockType['description']) }}</p>
                @endif
            </div>
        </div>

        <div class="flex gap-1">
            <x-ui.button
                variant="outline"
                size="icon"
                class="size-8"
                :disabled="$index === 0"
                wire:click="moveBlock({{ Js::from($block['id']) }}, {{ $index - 1 }})"
                :aria-label="__('Move up')"
            >
                <x-lucide-chevron-up />
            </x-ui.button>
            <x-ui.button
                variant="outline"
                size="icon"
                class="size-8"
                :disabled="$index === count($blocks) - 1"
                wire:click="moveBlock({{ Js::from($block['id']) }}, {{ $index + 1 }})"
                :aria-label="__('Move down')"
            >
                <x-lucide-chevron-down />
            </x-ui.button>
            @if ($blockType)
                <x-ui.button
                    variant="outline"
                    size="icon"
                    class="size-8"
                    wire:click="duplicateBlock({{ Js::from($block['id']) }})"
                    :aria-label="__('Duplicate')"
                >
                    <x-lucide-copy />
                </x-ui.button>
            @endif
            <x-ui.button
                variant="outline"
                size="icon"
                class="text-destructive hover:text-destructive ms-auto size-8"
                wire:click="removeBlock({{ Js::from($block['id']) }})"
                :aria-label="__('Remove')"
            >
                <x-lucide-trash-2 />
            </x-ui.button>
        </div>

        @if (! $blockType)
            <p class="text-muted-foreground text-sm">
                {{ __('The :type block type is not available, so the block is kept as it is but not shown on the site.', ['type' => $block['type']]) }}
            </p>
        @elseif ($fields = BlockType::fields($block['type']))
            <x-fields :$fields :values="$block['data']" :path="'blocks.'.$index.'.data'" />
        @endif

        <x-ui.separator />

        <x-ui.field-set>
            <x-ui.field-legend>{{ __('Advanced') }}</x-ui.field-legend>
            <x-fields
                :fields="BlockType::attributeFields()"
                :values="$block['attributes'] ?? []"
                :path="'blocks.'.$index.'.attributes'"
            />
        </x-ui.field-set>
    </div>
@endif
