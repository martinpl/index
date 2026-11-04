<?php

namespace App\Listing;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use LogicException;

/**
 * A list of records with tabs, search, filters, sorting, pagination and actions, similar to WordPress list tables and DataViews.
 *
 * The records come from an Eloquent query, or from a collection keyed by record key, like the installed plugins.
 *
 * @phpstan-type State array{tab?: string|null, search?: string, sort?: string|null, direction?: string|null, filters?: array<string, mixed>, perPage?: int|null}
 */
class Listing
{
    use Conditionable, Macroable;

    /**
     * @var list<Column>
     */
    protected array $columns = [];

    /**
     * @var list<Action>
     */
    protected array $actions = [];

    /**
     * @var list<Tab>
     */
    protected array $tabs = [];

    /**
     * @var array{0: string, 1: 'asc'|'desc'}|null
     */
    protected ?array $defaultSort = null;

    protected ?string $reorderColumn = null;

    /**
     * @var array<string, 'asc'|'desc'>
     */
    protected array $reorderThenBy = [];

    protected int $perPage = 20;

    /**
     * @var list<int>
     */
    protected array $perPageOptions = [10, 20, 50, 100];

    /**
     * @param  Closure(): (Builder<Model>|Collection<array-key, mixed>)  $data
     */
    final public function __construct(protected Closure $data) {}

    /**
     * @param  Closure(): (Builder<Model>|Collection<array-key, mixed>)  $data
     */
    public static function make(Closure $data): static
    {
        return new static($data);
    }

    /**
     * @param  list<Column>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ?Column
    {
        return collect($this->columns)->first(fn (Column $column): bool => $column->name === $name);
    }

    /**
     * @param  list<Action>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return list<Action>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getAction(string $name): ?Action
    {
        return collect($this->actions)->first(fn (Action $action): bool => $action->name === $name);
    }

    /**
     * @param  list<Tab>  $tabs  The first tab is selected by default.
     */
    public function tabs(array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }

    /**
     * @return list<Tab>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function getTab(string $name): ?Tab
    {
        return collect($this->tabs)->first(fn (Tab $tab): bool => $tab->name === $name);
    }

    /**
     * The number of records in each tab, keyed by tab name.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return collect($this->tabs)
            ->mapWithKeys(fn (Tab $tab): array => [$tab->name => $this->newData([], $tab->name)->count()])
            ->all();
    }

    /**
     * @param  'asc'|'desc'  $direction
     */
    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = [$column, $direction];

        return $this;
    }

    /**
     * Allow reordering the records by dragging them while sorted by the given database column, which stores their position.
     *
     * @param  array<string, 'asc'|'desc'>  $thenBy  The database columns sorting the records sharing a position, like new records.
     */
    public function reorderable(string $column = 'order', array $thenBy = []): static
    {
        $this->reorderColumn = $column;
        $this->reorderThenBy = $thenBy;

        return $this;
    }

    public function getReorderColumn(): ?string
    {
        return $this->reorderColumn;
    }

    /**
     * Whether the records may be dragged into a new order with the given resolved state.
     *
     * @param  array{sort: string|null, direction: 'asc'|'desc'}  $state
     */
    public function isReorderable(array $state): bool
    {
        return $this->reorderColumn !== null && $state['sort'] === $this->reorderColumn && $state['direction'] === 'asc';
    }

    public function isSortableBy(string $name): bool
    {
        return ($this->getColumn($name)?->isSortable() ?? false) || ($name !== '' && $name === $this->reorderColumn);
    }

    /**
     * @param  list<int>|null  $options
     */
    public function perPage(int $perPage, ?array $options = null): static
    {
        $this->perPage = $perPage;
        $this->perPageOptions = $options ?? $this->perPageOptions;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    /**
     * The state with every value the listing does not support replaced by its default.
     *
     * @param  State  $state
     * @return array{tab: string|null, search: string, sort: string|null, direction: 'asc'|'desc', filters: array<string, string>, perPage: int}
     */
    public function resolveState(array $state): array
    {
        $isSorted = $this->isSortableBy((string) ($state['sort'] ?? ''));

        return [
            'tab' => $this->getTab((string) ($state['tab'] ?? ''))->name ?? $this->tabs[0]->name ?? null,
            'search' => trim((string) ($state['search'] ?? '')),
            'sort' => $isSorted ? $state['sort'] : $this->defaultSort[0] ?? null,
            'direction' => match (true) {
                $isSorted && in_array($state['direction'] ?? null, ['asc', 'desc'], true) => $state['direction'],
                $isSorted => 'asc',
                default => $this->defaultSort[1] ?? 'asc',
            },
            'filters' => collect($state['filters'] ?? [])
                ->filter(fn (mixed $value, string $name): bool => is_string($value)
                    && $this->getColumn($name)?->isFilterable()
                    && array_key_exists($value, $this->getColumn($name)->getOptions()))
                ->all(),
            'perPage' => in_array($state['perPage'] ?? null, $this->perPageOptions, true) ? $state['perPage'] : $this->perPage,
        ];
    }

    /**
     * The query or collection of the records of the tab matching the selected filters.
     *
     * @param  array<string, string>  $filters
     * @return Builder<Model>|Collection<array-key, mixed>
     */
    public function newData(array $filters = [], ?string $tab = null): Builder|Collection
    {
        $data = ($this->data)();

        if ($tab && $this->getTab($tab)) {
            $data = $this->getTab($tab)->apply($data);
        }

        foreach ($filters as $name => $value) {
            $data = $this->getColumn($name)?->applyFilter($data, $value) ?? $data;
        }

        return $data;
    }

    /**
     * The records of the tab matching the selected filters with the given keys, keyed by record key.
     *
     * @param  array<int, int|string>  $keys
     * @param  array<string, string>  $filters
     * @return Collection<array-key, mixed>
     */
    public function find(array $keys, array $filters = [], ?string $tab = null): Collection
    {
        $data = $this->newData($filters, $tab);

        if ($data instanceof Builder) {
            return $data->whereKey($keys)->get()->keyBy(fn (Model $record): int|string => $record->getKey());
        }

        return $data->only($keys);
    }

    /**
     * The page of records matching the state, keyed by record key.
     *
     * @param  State  $state
     * @return LengthAwarePaginatorContract<array-key, mixed>
     */
    public function paginate(array $state): LengthAwarePaginatorContract
    {
        $state = $this->resolveState($state);
        $data = $this->newData($state['filters'], $state['tab']);
        $searchable = array_filter($this->columns, fn (Column $column): bool => $column->isSearchable());

        return $data instanceof Builder
            ? $this->paginateQuery($data, $state, $searchable)
            : $this->paginateCollection($data, $state, $searchable);
    }

    /**
     * Move the record with the given key to the given position on the page of records matching the state,
     * renumbering the positions of every record so they stay unique.
     *
     * @param  State  $state
     */
    public function reorder(int|string $key, int $position, array $state): void
    {
        $state = $this->resolveState($state);

        if (! $this->isReorderable($state)) {
            return;
        }

        $keys = $this->paginate($state)->getCollection()->keys()->map(fn (int|string $key): string => (string) $key);

        if (! $keys->contains((string) $key)) {
            return;
        }

        $siblings = $keys->reject(fn (string $sibling): bool => $sibling === (string) $key)->values();

        if ($siblings->isEmpty()) {
            return;
        }

        $position = max(0, min($position, $siblings->count()));
        $isBefore = $position < $siblings->count();
        $neighbor = $siblings[$isBefore ? $position : $position - 1];

        $query = ($this->data)();

        throw_unless($query instanceof Builder, LogicException::class, 'Only listings of queries can be reordered.');

        $positions = $this->orderByPosition($query, 'asc')
            ->pluck($query->qualifyColumn($this->reorderColumn), $query->getModel()->getQualifiedKeyName())
            ->mapWithKeys(fn (mixed $position, int|string $key): array => [(string) $key => (int) $position]);

        $order = $positions->keys()->map(fn (int|string $other): string => (string) $other)->reject(fn (string $other): bool => $other === (string) $key)->values()->all();
        array_splice($order, array_search($neighbor, $order, true) + ($isBefore ? 0 : 1), 0, [(string) $key]);

        foreach ($order as $index => $other) {
            if ($positions[$other] !== $index) {
                ($this->data)()->whereKey($other)->toBase()->update([$query->qualifyColumn($this->reorderColumn) => $index]);
            }
        }
    }

    /**
     * Sort the query by the position of the records, then by the columns sorting the records sharing a position.
     *
     * @param  Builder<Model>  $query
     * @param  'asc'|'desc'  $direction
     * @return Builder<Model>
     */
    protected function orderByPosition(Builder $query, string $direction): Builder
    {
        $query->orderBy($query->qualifyColumn($this->reorderColumn), $direction);

        foreach ($this->reorderThenBy as $column => $thenDirection) {
            $query->orderBy($query->qualifyColumn($column), $thenDirection);
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName(), $direction);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{search: string, sort: string|null, direction: 'asc'|'desc', perPage: int}  $state
     * @param  array<int, Column>  $searchable
     * @return LengthAwarePaginatorContract<array-key, Model>
     */
    protected function paginateQuery(Builder $query, array $state, array $searchable): LengthAwarePaginatorContract
    {
        if ($state['search'] !== '' && $searchable) {
            $query->where(function (Builder $query) use ($searchable, $state): void {
                foreach ($searchable as $column) {
                    $query->orWhereLike($query->qualifyColumn($column->name), "%{$state['search']}%");
                }
            });
        }

        if ($state['sort'] && $state['sort'] === $this->reorderColumn) {
            $this->orderByPosition($query, $state['direction']);
        } elseif ($state['sort']) {
            $query->orderBy($query->qualifyColumn($state['sort']), $state['direction'])
                ->orderBy($query->getModel()->getQualifiedKeyName(), $state['direction']);
        }

        return tap($query->paginate($state['perPage']), function (LengthAwarePaginator $paginator): void {
            $paginator->setCollection($paginator->getCollection()->keyBy(fn (Model $record): int|string => $record->getKey()));
        });
    }

    /**
     * @param  Collection<array-key, mixed>  $records
     * @param  array{search: string, sort: string|null, direction: 'asc'|'desc', perPage: int}  $state
     * @param  array<int, Column>  $searchable
     * @return LengthAwarePaginatorContract<array-key, mixed>
     */
    protected function paginateCollection(Collection $records, array $state, array $searchable): LengthAwarePaginatorContract
    {
        if ($state['search'] !== '' && $searchable) {
            $records = $records->filter(fn (mixed $record): bool => collect($searchable)->contains(
                fn (Column $column): bool => Str::contains((string) data_get($record, $column->name), $state['search'], true)
            ));
        }

        if ($state['sort']) {
            $records = $records->sortBy(fn (mixed $record): mixed => data_get($record, $state['sort']), SORT_NATURAL | SORT_FLAG_CASE, $state['direction'] === 'desc');
        }

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator($records->forPage($page, $state['perPage']), $records->count(), $state['perPage'], $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }
}
