@props(['field', 'path', 'value' => []])

<x-ui.field-set>
    <x-ui.field-legend>{{ $field->getLabel() }}</x-ui.field-legend>
    @if ($field->getDescription())
        <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
    @endif
    <x-ui.field-error :messages="$errors->get($path)" />
    @foreach ($value ?? [] as $index => $row)
        <div wire:key="{{ $path }}.{{ $index }}" class="flex flex-col gap-4 rounded-lg border p-4">
            <x-ui.field-group>
                @foreach ($field->getFields() as $child)
                    <x-dynamic-component
                        :component="$child->component"
                        :field="$child"
                        :path="$path.'.'.$index.'.'.$child->name"
                        :value="$row[$child->name] ?? null"
                    />
                @endforeach
            </x-ui.field-group>
            <div class="flex justify-end">
                <x-ui.button
                    variant="ghost"
                    size="sm"
                    type="button"
                    x-on:click="$wire.$set(@js($path), $wire.$get(@js($path)).filter((row, index) => index !== {{ $index }}))"
                >
                    <x-lucide-trash-2 />
                    {{ __('Remove') }}
                </x-ui.button>
            </div>
        </div>
    @endforeach
    <div>
        <x-ui.button
            variant="outline"
            size="sm"
            type="button"
            x-on:click="$wire.$set(@js($path), [...($wire.$get(@js($path)) ?? []), @js($field->getRowDefault())])"
        >
            <x-lucide-plus />
            {{ __('Add') }}
        </x-ui.button>
    </div>
</x-ui.field-set>
