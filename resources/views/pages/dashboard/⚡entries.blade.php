<?php

use App\Enums\EntryStatus;
use App\Facades\Action as ActionHook;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use App\Models\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public string $type;

    public function mount(string $type): void
    {
        abort_unless(EntryType::has($type), 404);
        // Authorized before `booted()` too, which runs after mounting, so the hook only runs for users who may list the entries.
        abort_unless(Gate::allows(EntryType::get($type)['can']), 403);

        $this->type = $type;

        ActionHook::run(['entries.listing', "entries.$type.listing"], $type);
    }

    public function booted(): void
    {
        abort_unless(Gate::allows(EntryType::get($this->type)['can']), 403);
    }

    /**
     * The entries of the type, which plugins may change through the `entries.records` filter, receiving the entry type,
     * and then the `entries.{type}.records` filter of the type, like listing the layouts added by the theme along with
     * the saved ones, as a collection keyed by record key.
     *
     * @return Builder<Entry>|Collection<array-key, Entry>
     */
    protected function records(): Builder|Collection
    {
        return Filter::apply(['entries.records', "entries.{$this->type}.records"], Entry::withAnyStatus()->with('user')->where('type', $this->type), $this->type);
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        return [
            Tab::make('all')->label(__('All'))->query(fn (Builder|Collection $entries) => $entries->where('status', '!=', EntryStatus::Trash->value)),
            Tab::make('mine')->label(__('Mine'))->query(fn (Builder|Collection $entries) => $entries->where('status', '!=', EntryStatus::Trash->value)->where('user_id', Auth::id())),
            Tab::make('publish')->label(__('Published'))->query(fn (Builder|Collection $entries) => $entries->where('status', EntryStatus::Publish->value)),
            Tab::make('draft')->label(__('Drafts'))->query(fn (Builder|Collection $entries) => $entries->where('status', EntryStatus::Draft->value)),
            Tab::make('trash')->label(__('Trash'))->query(fn (Builder|Collection $entries) => $entries->where('status', EntryStatus::Trash->value)),
        ];
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('Title'))
                ->sortable()
                ->searchable()
                ->hideable(false)
                ->url(fn (Entry $entry): string => $entry->editUrl())
                ->states(fn (Entry $entry): array => Filter::apply(['entry.states', "entries.{$this->type}.states"], [
                    'draft' => when($entry->status === EntryStatus::Draft->value && $this->tab !== 'draft', __('Draft')),
                ], $entry)),
            Column::make('user')->label(__('Author'))->render(fn (Entry $entry): ?string => $entry->user?->name),
            Column::make('date')->label(__('Date'))->sortable()->dateTime(),
            Column::make('modified')->label(__('Modified'))->sortable()->hidden()->dateTime(),
        ];
    }

    /**
     * The actions on the entries, which plugins may change through the `entries.actions` filter, receiving the entry type,
     * and then the `entries.{type}.actions` filter of the type.
     *
     * @return list<Action>
     */
    protected function actions(): array
    {
        return Filter::apply(['entries.actions', "entries.{$this->type}.actions"], [
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('lucide-pencil')
                ->visible(fn (Entry $entry): bool => ! $entry->trashed())
                ->url(fn (Entry $entry): string => $entry->editUrl()),
            Action::make('view')
                ->label(__('View'))
                ->icon('lucide-external-link')
                ->visible(fn (Entry $entry): bool => $entry->status === EntryStatus::Publish->value)
                ->url(fn (Entry $entry): string => $entry->url()),
            Action::make('trash')
                ->label(__('Move to trash'))
                ->icon('lucide-trash')
                ->bulk()
                ->visible(fn (Entry $entry): bool => $entry->trashable())
                ->handle(fn (Collection $entries) => $entries->each->delete())
                ->successMessage(fn (Collection $entries): string => trans_choice(':count entry moved to the trash.|:count entries moved to the trash.', $entries->count())),
            Action::make('restore')
                ->label(__('Restore'))
                ->icon('lucide-undo-2')
                ->bulk()
                ->visible(fn (Entry $entry): bool => $entry->trashed())
                ->handle(fn (Collection $entries) => $entries->each->restore())
                ->successMessage(fn (Collection $entries): string => trans_choice(':count entry restored.|:count entries restored.', $entries->count())),
            Action::make('delete')
                ->label(__('Delete permanently'))
                ->icon('lucide-trash-2')
                ->destructive()
                ->bulk()
                ->confirm(__('Are you sure you want to permanently delete the selected entries?'))
                ->visible(fn (Entry $entry): bool => $entry->trashed())
                ->handle(fn (Collection $entries) => $entries->each->forceDelete())
                ->successMessage(fn (Collection $entries): string => trans_choice(':count entry permanently deleted.|:count entries permanently deleted.', $entries->count())),
        ], $this->type);
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('order')->reorderable(thenBy: ['date' => 'desc']);
    }

    public function render(): View
    {
        return $this->view()->title(__(Str::plural(EntryType::get($this->type)['name'])));
    }
};
?>

<x-slot:actions>
    <x-ui.button :href="route('dashboard.entries.create', $type)" size="sm" wire:navigate>
        <x-lucide-plus />
        {{ __('Add :type', ['type' => __(\App\Facades\EntryType::get($type)['name'])]) }}
    </x-ui.button>
</x-slot:actions>

<x-listing :$listing />
