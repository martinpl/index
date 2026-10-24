<?php

namespace App\Listing;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Lists the component's records with its columns, actions and tabs, passing them to its view
 * as `$listing`, ready for the `<x-listing :$listing />` component.
 */
trait WithListing
{
    use WithPagination;

    #[Url]
    public ?string $tab = null;

    #[Url(except: '')]
    public string $search = '';

    #[Url]
    public ?string $sort = null;

    #[Url]
    public ?string $direction = null;

    /**
     * @var array<string, string>
     */
    #[Url]
    public array $filters = [];

    public ?int $perPage = null;

    /**
     * @var list<string>
     */
    public array $hiddenColumns = [];

    /**
     * The keys of the selected records.
     *
     * @var list<string>
     */
    public array $selected = [];

    protected ?Listing $resolvedListing = null;

    /**
     * The query of the records, or a collection of them keyed by record key.
     *
     * @return Builder<Model>|Collection<array-key, mixed>
     */
    abstract protected function records(): Builder|Collection;

    /**
     * @return list<Column>
     */
    abstract protected function columns(): array;

    /**
     * @return list<Action>
     */
    protected function actions(): array
    {
        return [];
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        return [];
    }

    /**
     * Configure the listing, like its default sort or number of records per page.
     */
    protected function configure(Listing $listing): void
    {
        //
    }

    public function mountWithListing(): void
    {
        $this->hiddenColumns = collect($this->getListing()->getColumns())
            ->filter(fn (Column $column): bool => $column->isHidden())
            ->map(fn (Column $column): string => $column->name)
            ->values()
            ->all();

        $this->perPage = $this->getListing()->resolveState($this->getListingState())['perPage'];
    }

    public function renderingWithListing(View $view): void
    {
        $records = $this->getListing()->paginate($this->getListingState());

        if ($records->isEmpty() && $records->currentPage() > 1) {
            $this->setPage($records->lastPage());

            $records = $this->getListing()->paginate($this->getListingState());
        }

        $view->with('listing', [
            'records' => $records,
            'columns' => $this->getListing()->getColumns(),
            'actions' => $this->getListing()->getActions(),
            'bulkActions' => $this->getBulkActions($records->getCollection()),
            'tabs' => $this->getListing()->getTabs(),
            'state' => [
                'counts' => $this->getListing()->counts(),
                ...$this->getListing()->resolveState($this->getListingState()),
                'perPageOptions' => $this->getListing()->getPerPageOptions(),
                'hiddenColumns' => $this->hiddenColumns,
                'selected' => $this->selected,
            ],
        ]);
    }

    public function updatedWithListing(string $property): void
    {
        if (in_array(explode('.', $property)[0], ['tab', 'search', 'filters', 'perPage'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    /**
     * Sort by the given column, reversing the direction when it is already sorted by it.
     */
    public function sortBy(string $column): void
    {
        $state = $this->getListing()->resolveState($this->getListingState());

        if (! $this->getListing()->getColumn($column)?->isSortable()) {
            return;
        }

        $this->direction = $state['sort'] === $column && $state['direction'] === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function toggleColumn(string $column): void
    {
        if (! $this->getListing()->getColumn($column)?->isHideable()) {
            return;
        }

        $this->hiddenColumns = in_array($column, $this->hiddenColumns, true)
            ? array_values(array_diff($this->hiddenColumns, [$column]))
            : [...$this->hiddenColumns, $column];
    }

    /**
     * Run the action on the given record, or on the selected records when it is a bulk action.
     */
    public function runAction(string $action, int|string|null $key = null): void
    {
        $action = $this->getListing()->getAction($action);

        abort_if(is_null($action) || (is_null($key) && ! $action->isBulk()), 404);

        $state = $this->getListing()->resolveState($this->getListingState());

        $records = $this->getListing()->find(is_null($key) ? $this->selected : [$key], $state['filters'], $state['tab']);

        $message = $action->run($records);

        $this->selected = [];

        if ($message) {
            $this->dispatch('toast', type: 'success', title: $message);
        }
    }

    /**
     * The bulk actions that may run on at least one of the given records.
     *
     * @param  Collection<array-key, mixed>  $records
     * @return list<Action>
     */
    protected function getBulkActions(Collection $records): array
    {
        return array_values(array_filter(
            $this->getListing()->getActions(),
            fn (Action $action): bool => $action->isBulk() && $records->contains(fn (mixed $record): bool => $action->isVisibleFor($record)),
        ));
    }

    protected function getListing(): Listing
    {
        return $this->resolvedListing ??= tap(
            Listing::make($this->records(...))->columns($this->columns())->actions($this->actions())->tabs($this->tabs()),
            $this->configure(...),
        );
    }

    /**
     * @return array{tab: string|null, search: string, sort: string|null, direction: string|null, filters: array<string, string>, perPage: int|null}
     */
    protected function getListingState(): array
    {
        return [
            'tab' => $this->tab,
            'search' => $this->search,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'filters' => $this->filters,
            'perPage' => $this->perPage,
        ];
    }
}
