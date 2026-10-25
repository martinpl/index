@props(['fields', 'values' => [], 'path' => 'values'])

<x-ui.field-group {{ $attributes }}>
    @foreach ($fields as $field)
        <x-dynamic-component
            :component="$field->component"
            :field="$field"
            :path="$path.'.'.$field->name"
            :value="$values[$field->name] ?? null"
        />
    @endforeach
</x-ui.field-group>
