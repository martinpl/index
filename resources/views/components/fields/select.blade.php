@props(['field', 'path', 'value' => null])

<x-ui.field>
    <x-ui.field-label :for="$field->id($path)">{{ $field->getLabel() }}</x-ui.field-label>
    <x-ui.select :id="$field->id($path)" :value="$value ?? ''" wire:model="{{ $path }}">
        <x-ui.select-trigger class="w-full" :aria-label="$field->getLabel()">
            <x-ui.select-value :placeholder="__('Select an option')" />
        </x-ui.select-trigger>
        <x-ui.select-content>
            @foreach ($field->getOptions() as $optionValue => $optionLabel)
                <x-ui.select-item :value="(string) $optionValue">{{ $optionLabel }}</x-ui.select-item>
            @endforeach
        </x-ui.select-content>
    </x-ui.select>
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.field-error :messages="$errors->get($path)" />
</x-ui.field>
