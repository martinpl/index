@props(['field', 'path', 'value' => null])

@php
    use App\Entries\Layout;
    use App\Facades\EntryType;
    use Illuminate\Support\Facades\Gate;

    $layouts = $field->getOptions();
@endphp

<div class="flex flex-col gap-3">
    <x-ui.field>
        <x-ui.field-label :for="$field->id($path)">{{ $field->getLabel() }}</x-ui.field-label>
        <x-ui.select :id="$field->id($path)" :value="$value ?? ''" wire:model.live="{{ $path }}">
            <x-ui.select-trigger class="w-full" :aria-label="$field->getLabel()">
                <x-ui.select-value :placeholder="__('Select an option')" />
            </x-ui.select-trigger>
            <x-ui.select-content>
                @foreach ($layouts as $id => $name)
                    <x-ui.select-item :value="(string) $id">{{ $name }}</x-ui.select-item>
                @endforeach
            </x-ui.select-content>
        </x-ui.select>
        @if ($field->getDescription())
            <x-ui.field-description>{{ $field->getDescription() }}</x-ui.field-description>
        @endif
        <x-ui.field-error :messages="$errors->get($path)" />
    </x-ui.field>

    @if (filled($value) && array_key_exists($value, $layouts) && Gate::allows(EntryType::get(Layout::$type)['can']))
        <x-ui.button :href="Layout::named($value)->editUrl()" variant="outline" size="sm" target="_blank">
            <x-lucide-pencil />
            {{ __('Edit layout') }}
        </x-ui.button>
    @endif
</div>
