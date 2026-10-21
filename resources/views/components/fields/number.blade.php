@props(['field', 'path', 'value' => null])

<x-ui.field>
    <x-ui.field-label :for="$field->id($path)">{{ $field->getLabel() }}</x-ui.field-label>
    <x-ui.input
        :id="$field->id($path)"
        type="number"
        :min="$field->min"
        :max="$field->max"
        :step="$field->step"
        :required="$field->isRequired()"
        wire:model="{{ $path }}"
    />
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.field-error :messages="$errors->get($path)" />
</x-ui.field>
