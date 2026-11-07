@props(['field', 'path', 'value' => null, 'formats' => []])

@php
    $isCustom = filled($value) && ! in_array($value, $formats, true);
@endphp

<x-ui.field-set x-on:input.debounce.300ms="$wire.$refresh()" x-on:click.debounce.300ms="$wire.$refresh()">
    <x-ui.field-legend variant="label">{{ $field->getLabel() }}</x-ui.field-legend>
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    {{-- The group holds "__custom" for the custom option, so the property gets the typed format instead. --}}
    <div x-data="{ custom: {{ Js::from($isCustom ? $value : '') }} }">
        <x-ui.radio-group
            :value="$isCustom ? '__custom' : $value"
            x-effect="$wire.$set('{{ $path }}', value === '__custom' ? custom : value, false)"
        >
            @foreach ($formats as $format)
                <x-ui.field orientation="horizontal">
                    <x-ui.radio-group-item :id="$field->id($path).'-'.$format" :value="$format" />
                    <x-ui.field-label :for="$field->id($path).'-'.$format" class="font-normal">
                        {{ now()->inTimezone()->translatedFormat($format) }} ({{ $format }})
                    </x-ui.field-label>
                </x-ui.field>
            @endforeach
            <x-ui.field orientation="horizontal">
                <x-ui.radio-group-item :id="$field->id($path).'-custom'" value="__custom" />
                <x-ui.field-label :for="$field->id($path).'-custom'" class="font-normal">
                    {{ __('Custom') }}</x-ui.field-label>
                <x-ui.input
                    x-model="custom"
                    x-on:focus="
                        value = '__custom';
                        rovingValue = '__custom';
                    "
                    :aria-label="__('Custom').' '.$field->getLabel()"
                    class="h-8 w-40"
                />
            </x-ui.field>
        </x-ui.radio-group>
    </div>
    @if ($isCustom)
        <x-ui.field-description>
            {{ __('Preview:') }} {{ now()->inTimezone()->translatedFormat($value) }}</x-ui.field-description>
    @endif
    <x-ui.field-error :messages="$errors->get($path)" />
</x-ui.field-set>
