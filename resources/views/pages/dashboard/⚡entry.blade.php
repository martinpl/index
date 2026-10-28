<?php

use App\Blocks\WithBlocks;
use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Fields\Field;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\WithFields;
use App\Models\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    use WithBlocks;
    use WithFields;

    public string $type;

    /**
     * The entry being edited, or null when adding a new one.
     */
    public ?Entry $record = null;

    public string $name = '';

    public function mount(string $type, ?string $entry = null): void
    {
        abort_unless(EntryType::has($type), 404);

        $this->type = $type;
        $this->record = $entry ? Entry::withAnyStatus()->where('type', $type)->findOrFail($entry) : null;
        $this->name = $this->record->name ?? '';

        $this->fillValues($this->record?->only(['slug', 'status']) ?? []);
        $this->fillBlocks($this->record->content ?? []);
    }

    public function booted(): void
    {
        abort_unless(Gate::allows(EntryType::get($this->type)['can']), 403);
    }

    /**
     * Validate and store the entry, going on to edit it when it was just added.
     */
    public function save(): void
    {
        $form = $this->form();

        try {
            $this->validate(
                ['name' => ['required', 'string', 'max:255'], ...$form->getRules('values'), ...$this->blockRules()],
                [],
                ['name' => __('title'), ...$form->getValidationAttributes('values'), ...$this->blockValidationAttributes()],
            );
        } catch (ValidationException $exception) {
            $this->selectInvalidBlock(array_keys($exception->errors()));

            throw $exception;
        }

        $attributes = ['name' => $this->name, ...$form->cast($this->values), 'content' => $this->castBlocks()];
        $typeName = __(EntryType::get($this->type)['name']);

        if ($this->record) {
            $this->record->update($attributes);

            $this->fillValues($this->record->only(['slug', 'status']));
            $this->fillBlocks($this->record->content);

            $this->dispatch('toast', type: 'success', title: __(':type updated.', ['type' => $typeName]));

            return;
        }

        $entry = Entry::create([...$attributes, 'type' => $this->type, 'user_id' => Auth::id(), 'owner' => 'core']);

        $this->dispatch('toast', type: 'success', title: __(':type added.', ['type' => $typeName]));

        $this->redirectRoute('dashboard.entries.edit', ['type' => $this->type, 'entry' => $entry], navigate: true);
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        return [
            Select::make('status')
                ->label(__('Status'))
                ->options([EntryStatus::Draft->value => __('Draft'), EntryStatus::Publish->value => __('Published')])
                ->default(EntryStatus::Draft->value)
                ->required(),
            Text::make('slug')
                ->label(__('Slug'))
                ->description(__('The URL-friendly version of the title, generated from it when left empty.'))
                ->rules('alpha_dash', 'lowercase', 'max:255', Rule::unique(Entry::class, 'slug')->where('type', $this->type)->ignore($this->record)),
        ];
    }

    public function render(): View
    {
        $typeName = __(EntryType::get($this->type)['name']);

        return $this->view()->title($this->record ? __('Edit :type', ['type' => $typeName]) : __('Add :type', ['type' => $typeName]));
    }
};
?>

<form wire:submit="save" class="-m-4 grid flex-1 gap-6 xl:grid-cols-[15rem_minmax(0,1fr)_20rem]">
    @teleport('#actions')
        <div class="flex flex-1 items-center justify-end gap-2">
            <x-ui.field class="flex-1 md:absolute md:left-1/2 md:w-md md:-translate-x-1/2">
                <x-ui.input
                    wire:model="name"
                    :placeholder="__('Add title')"
                    :aria-label="__('Title')"
                    class="text-center font-medium"
                />
                <x-ui.field-error :messages="$errors->get('name')" />
            </x-ui.field>
            <div class="flex shrink-0 gap-2">
                @if ($record?->status === \App\Enums\EntryStatus::Publish->value)
                    <x-ui.button :href="$record->url()" variant="outline" size="sm" target="_blank">
                        <x-lucide-external-link />
                        {{ __('View') }}
                    </x-ui.button>
                @endif
                <x-ui.button size="sm" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    {{ $record ? __('Update') : __('Save') }}
                </x-ui.button>
            </div>
        </div>
    @endteleport

    <aside class="border-r">
        <div class="p-3 xl:sticky xl:top-26 xl:max-h-[calc(100svh-6.5rem)] xl:overflow-y-auto">
            <x-block-editor.structure :$blocks :$blockTypes :$selectedBlock />
        </div>
    </aside>

    <div class="bg-background px-6 py-10 sm:px-12">
        <x-block-editor.preview :$blocks :$blockTypes :$selectedBlock class="mx-auto w-full max-w-2xl" />
    </div>

    <aside class="border-l">
        <div class="flex flex-col gap-4 p-4 xl:sticky xl:top-26 xl:max-h-[calc(100svh-4rem)] xl:overflow-y-auto">
            <x-ui.tabs wire:key="settings-tabs-{{ $selectedBlock ? 'block' : 'entry' }}" :value="$selectedBlock ? 'block' : 'entry'">
                <x-ui.tabs-list class="w-full">
                    <x-ui.tabs-trigger value="entry" class="flex-1" wire:click="selectBlock(null)">
                        {{ __(\App\Facades\EntryType::get($type)['name']) }}
                    </x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="block" class="flex-1" :disabled="! $selectedBlock">
                        {{ __('Block') }}
                    </x-ui.tabs-trigger>
                </x-ui.tabs-list>
            </x-ui.tabs>

            @if ($selectedBlock)
                <x-block-editor.settings :$blocks :$blockTypes :$selectedBlock />
            @else
                <x-fields :$fields :$values />
            @endif
        </div>
    </aside>
</form>
