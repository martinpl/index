@if (empty($terms))
    <p class="text-muted-foreground text-sm">{{ __('No :terms yet.', ['terms' => Str::lower(__($name))]) }}</p>
@else
    <div class="flex max-h-64 flex-col gap-2 overflow-y-auto">
        @foreach ($terms as ['term' => $term, 'depth' => $depth])
            <x-ui.field
                orientation="horizontal"
                wire:key="term-{{ $term->id }}"
                @style(['padding-left: '.($depth * 1.25).'rem' => $depth])
            >
                <x-ui.checkbox native id="term-{{ $term->id }}" :value="$term->id" wire:model="terms.{{ $termType }}" />
                <x-ui.field-label for="term-{{ $term->id }}" class="font-normal">{{ $term->name }}</x-ui.field-label>
            </x-ui.field>
        @endforeach
    </div>
    <x-ui.field-error :messages='collect($errors->get("terms.$termType.*"))->flatten()->all()' class="mt-2" />
@endif
