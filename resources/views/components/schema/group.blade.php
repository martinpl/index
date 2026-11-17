@props(['field', 'path', 'value' => []])

<x-ui.field-set>
    <x-ui.field-legend>{{ $field->getLabel() }}</x-ui.field-legend>
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.field-group>
        @foreach ($field->getSchema() as $child)
            <x-dynamic-component
                :component="$child->view"
                :field="$child"
                :path="$path.'.'.$child->name"
                :value="$value[$child->name] ?? null"
            />
        @endforeach
    </x-ui.field-group>
</x-ui.field-set>
