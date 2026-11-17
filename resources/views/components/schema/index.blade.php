@props(['schema', 'values' => [], 'path' => 'values'])

<x-ui.field-group {{ $attributes }}>
    @foreach ($schema as $child)
        <x-dynamic-component
            :component="$child->view"
            :field="$child"
            :path="$path.'.'.$child->name"
            :value="$values[$child->name] ?? null"
        />
    @endforeach
</x-ui.field-group>
