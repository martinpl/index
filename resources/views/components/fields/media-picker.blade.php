@props(['field', 'path', 'value' => null])

<x-ui.field>
    <x-ui.field-label>{{ $field->getLabel() }}</x-ui.field-label>
    <livewire:media-picker
        :type="$field->getType()"
        wire:model.live="{{ $path }}"
        wire:key="media-picker-{{ $path }}"
    />
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.field-error :messages="$errors->get($path)" />
</x-ui.field>
