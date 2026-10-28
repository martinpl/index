@props(['blockTypes', 'position' => null])

<x-ui.dropdown-menu>
    <x-ui.dropdown-menu-trigger> {{ $slot }} </x-ui.dropdown-menu-trigger>
    <x-ui.dropdown-menu-content {{ $attributes->merge(['class' => 'w-64']) }}>
        @foreach ($blockTypes as $blockType)
            <x-ui.dropdown-menu-item wire:click="addBlock({{ Js::from($blockType['key']) }}, {{ Js::from($position) }})">
                <x-dynamic-component :component="$blockType['icon']" />
                <div class="flex flex-col">
                    <span>{{ __($blockType['name']) }}</span>
                    @if ($blockType['description'])
                        <span class="text-muted-foreground text-xs">{{ __($blockType['description']) }}</span>
                    @endif
                </div>
            </x-ui.dropdown-menu-item>
        @endforeach
    </x-ui.dropdown-menu-content>
</x-ui.dropdown-menu>
