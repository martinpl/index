@props(['location', 'arguments' => [], 'values' => []])

@foreach (\App\Facades\Metabox::for($location) as $metabox)
    <x-ui.collapsible
        wire:key="metabox-{{ $metabox['key'] }}"
        {{ $attributes->twMerge('group/collapsible -mx-4 border-t px-4 pt-4') }}
        ::data-state="open ? 'open' : 'closed'"
    >
        <x-ui.sidebar-group-label
            class="group/label text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground w-full text-sm"
            x-on:click="open = ! open"
            ::data-state="open ? 'open' : 'closed'"
            role="button"
        >
            {{ __($metabox['with']['title']) }}
            <x-lucide-chevron-right class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
        </x-ui.sidebar-group-label>
        <x-ui.collapsible-content class="mt-2 flex flex-col gap-4">
            @if ($fields = \App\Facades\Metabox::fields($metabox['key']))
                <x-fields :$fields :values="$values[$metabox['key']] ?? []" :path="'metaboxes.'.$metabox['key']" />
            @endif
            @if ($metabox['render'])
                {{ $metabox['render'](...$arguments) }}
            @endif
        </x-ui.collapsible-content>
    </x-ui.collapsible>
@endforeach
