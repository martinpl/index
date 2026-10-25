<?php

use App\Facades\TermType;
use App\Fields\Field;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Textarea;
use App\Fields\WithFields;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\WithListing;
use App\Models\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    use WithFields;
    use WithListing;

    public string $type;

    /**
     * The ID of the term being edited, or null when adding a new one.
     */
    public ?int $editing = null;

    public function mount(string $type): void
    {
        abort_unless(TermType::has($type), 404);

        $this->type = $type;
        $this->fillForm();
    }

    public function booted(): void
    {
        abort_unless(Gate::allows(TermType::get($this->type)['can']), 403);
    }

    /**
     * Validate and store the term being added or edited.
     */
    public function save(): void
    {
        $term = $this->editing ? Term::where('type', $this->type)->findOrFail($this->editing) : null;

        $attributes = $this->validateValues();
        $name = __(TermType::get($this->type)['name']);

        if ($term) {
            $term->update($attributes);

            $this->dispatch('toast', type: 'success', title: __(':type updated.', ['type' => $name]));
        } else {
            Term::create([...$attributes, 'type' => $this->type, 'owner' => 'core']);

            $this->dispatch('toast', type: 'success', title: __(':type added.', ['type' => $name]));
        }

        $this->resetForm();
    }

    /**
     * Stop editing the term and empty the form for adding a new one.
     */
    public function resetForm(): void
    {
        $this->fillForm();
        $this->resetValidation();
    }

    /**
     * Fill the form with the values of the given term, or with the defaults for adding a new one.
     */
    protected function fillForm(?Term $term = null): void
    {
        $this->editing = $term?->getKey();
        $this->fillValues($term?->attributesToArray() ?? []);
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        $parentOptions = $this->parentOptions();

        return [
            Text::make('name')
                ->label(__('Name'))
                ->description(__('The name is how it appears on your site.'))
                ->required()
                ->rules('string', 'max:255'),
            Text::make('slug')
                ->label(__('Slug'))
                ->description(__('The URL-friendly version of the name, generated from it when left empty.'))
                ->rules('alpha_dash', 'lowercase', 'max:255', Rule::unique(Term::class, 'slug')->where('type', $this->type)->ignore($this->editing)),
            Select::make('parent_id')
                ->label(__('Parent'))
                ->options($parentOptions)
                ->visible(TermType::get($this->type)['hierarchical'] && filled($parentOptions)),
            Textarea::make('description')
                ->label(__('Description'))
                ->rules('string'),
        ];
    }

    /**
     * The terms of the type that may be the parent of the edited term, excluding itself and its descendants.
     *
     * @return array<int, string>
     */
    protected function parentOptions(): array
    {
        $terms = Term::where('type', $this->type)->orderBy('name')->get(['id', 'name', 'parent_id']);
        $excluded = collect($this->editing ? [$this->editing] : []);

        do {
            $count = $excluded->count();
            $excluded = $excluded->merge($terms->whereIn('parent_id', $excluded)->modelKeys())->unique();
        } while ($excluded->count() > $count);

        return $terms->except($excluded->all())->pluck('name', 'id')->all();
    }

    /**
     * @return Builder<Term>
     */
    protected function records(): Builder
    {
        return Term::with('parent')->withCount('entries')->where('type', $this->type);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')->label(__('Name'))->sortable()->searchable()->hideable(false),
            Column::make('description')->label(__('Description'))->searchable(),
            Column::make('slug')->label(__('Slug'))->sortable()->searchable(),
            ...TermType::get($this->type)['hierarchical'] ? [Column::make('parent')->label(__('Parent'))->hidden()->render(fn (Term $term): ?string => $term->parent?->name)] : [],
            Column::make('entries_count')->label(__('Count')),
        ];
    }

    /**
     * @return list<Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('lucide-pencil')
                ->handle(fn (Collection $terms) => $this->fillForm($terms->first())),
            Action::make('delete')
                ->label(__('Delete'))
                ->icon('lucide-trash-2')
                ->destructive()
                ->bulk()
                ->confirm(__('Are you sure you want to delete the selected terms?'))
                ->handle(function (Collection $terms): void {
                    $terms->each->delete();

                    if ($this->editing && $terms->has($this->editing)) {
                        $this->resetForm();
                    }
                })
                ->successMessage(fn (Collection $terms): string => trans_choice(':count term deleted.|:count terms deleted.', $terms->count())),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('name');
    }

    public function render(): View
    {
        return $this->view()->title($this->editing ? __('Edit :type', ['type' => __(TermType::get($this->type)['name'])]) : __('Add :type', ['type' => __(TermType::get($this->type)['name'])]));
    }
};
?>

<div class="grid items-start gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(0,4fr)]">
    <form wire:submit="save" wire:key="term-form-{{ $editing ?? 'new' }}" class="flex flex-col gap-6">
        <x-fields :$fields :$values />
        <div class="flex gap-2">
            <x-ui.button type="submit">
                {{ $editing ? __('Update') : __('Add New :type', ['type' => __(TermType::get($type)['name'])]) }}
            </x-ui.button>
            @if ($editing)
                <x-ui.button type="button" variant="outline" wire:click="resetForm">{{ __('Cancel') }}</x-ui.button>
            @endif
        </div>
    </form>
    <x-listing :$listing />
</div>
