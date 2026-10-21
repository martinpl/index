@props(['field', 'path', 'value' => null])

<x-ui.field orientation="horizontal">
    <x-ui.switch :id="$field->id($path)" :checked="$value" wire:model="{{ $path }}" />
    <x-ui.field-content>
        <x-ui.field-label :for="$field->id($path)">{{ $field->getLabel() }}</x-ui.field-label>
        @if ($field->getDescription())
            <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
        @endif
        <x-ui.field-error :messages="$errors->get($path)" />
    </x-ui.field-content>
</x-ui.field>
