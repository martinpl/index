@props(['field', 'path', 'value' => null])

<x-ui.field-set>
    <x-ui.field-legend variant="label">{{ $field->getLabel() }}</x-ui.field-legend>
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.radio-group :value="$value" wire:model="{{ $path }}">
        @foreach ($field->getOptions() as $optionValue => $optionLabel)
            <x-ui.field orientation="horizontal">
                <x-ui.radio-group-item :id="$field->id($path).'-'.$optionValue" :value="(string) $optionValue" />
                <x-ui.field-label :for="$field->id($path).'-'.$optionValue" class="font-normal">
                    {{ $optionLabel }}
                </x-ui.field-label>
            </x-ui.field>
        @endforeach
    </x-ui.radio-group>
    <x-ui.field-error :messages="$errors->get($path)" />
</x-ui.field-set>
