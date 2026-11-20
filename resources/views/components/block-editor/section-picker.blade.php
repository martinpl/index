@props(['field', 'path', 'value' => null])

@php
    $sections = $field->getOptions();
@endphp

<div class="flex flex-col gap-3">
    <x-schema.select :$field :$path :$value />

    @if (filled($value) && array_key_exists($value, $sections))
        <x-ui.button
            :href="route('dashboard.entries.edit', [\App\Entries\Section::$type, $value])"
            variant="outline"
            size="sm"
            target="_blank"
        >
            <x-lucide-pencil />
            {{ __('Edit section') }}
        </x-ui.button>
    @elseif (empty($sections))
        <x-ui.button
            :href="route('dashboard.entries.create', \App\Entries\Section::$type)"
            variant="outline"
            size="sm"
            target="_blank"
        >
            <x-lucide-plus />
            {{ __('Add section') }}
        </x-ui.button>
    @endif
</div>
