<?php

use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use App\Models\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public string $type;

    public function mount(string $type): void
    {
        abort_unless(EntryType::has($type), 404);

        $this->type = $type;
    }

    public function booted(): void
    {
        abort_unless(Gate::allows(EntryType::get($this->type)['can']), 403);
    }

    /**
     * @return Builder<Entry>
     */
    protected function records(): Builder
    {
        return Entry::withAnyStatus()->with('user')->where('type', $this->type);
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        return [
            Tab::make('all')->label(__('All'))->query(fn (Builder $query) => $query->where('status', '!=', EntryStatus::Trash)),
            Tab::make('mine')->label(__('Mine'))->query(fn (Builder $query) => $query->where('status', '!=', EntryStatus::Trash)->where('user_id', Auth::id())),
            Tab::make('publish')->label(__('Published'))->query(fn (Builder $query) => $query->where('status', EntryStatus::Publish)),
            Tab::make('draft')->label(__('Drafts'))->query(fn (Builder $query) => $query->where('status', EntryStatus::Draft)),
            Tab::make('trash')->label(__('Trash'))->query(fn (Builder $query) => $query->where('status', EntryStatus::Trash)),
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
                ->render(fn (Entry $entry): HtmlString|string => $entry->status === EntryStatus::Draft->value && $this->tab !== 'draft'
                    ? new HtmlString(e($entry->name).' <span class="text-muted-foreground">— '.e(__('Draft')).'</span>')
                    : $entry->name),
            Column::make('user')->label(__('Author'))->render(fn (Entry $entry): ?string => $entry->user?->name),
            Column::make('date')->label(__('Date'))->sortable()->render(fn (Entry $entry): ?string => $entry->date),
            Column::make('modified')->label(__('Modified'))->sortable()->hidden()->render(fn (Entry $entry): ?string => $entry->modified),
        ];
    }

    /**
     * @return list<Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('view')
                ->label(__('View'))
                ->icon('lucide-external-link')
                ->visible(fn (Entry $entry): bool => $entry->status === EntryStatus::Publish->value)
                ->url(fn (Entry $entry): string => $entry->url()),
            Action::make('trash')
                ->label(__('Move to trash'))
                ->icon('lucide-trash')
                ->bulk()
                ->visible(fn (Entry $entry): bool => ! $entry->trashed())
                ->handle(fn (Collection $entries) => $entries->each->delete())
                ->successMessage(fn (Collection $entries): string => trans_choice(':count entry moved to the trash.|:count entries moved to the trash.', $entries->count())),
            Action::make('restore')
                ->label(__('Restore'))
                ->icon('lucide-undo-2')
                ->bulk()
                ->visible(fn (Entry $entry): bool => $entry->trashed())
                ->handle(fn (Collection $entries) => $entries->each->update(['status' => EntryStatus::Draft->value]))
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
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('date', 'desc');
    }

    public function render(): View
    {
        return $this->view()->title(__(Str::plural(EntryType::get($this->type)['name'])));
    }
};
?>

<x-listing :$listing />
