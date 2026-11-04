@props(['listing'])

@php
    use App\Listing\Action;
    use App\Listing\Column;
    use App\Listing\Tab;
    use Illuminate\Pagination\UrlWindow;

    ['records' => $records, 'columns' => $columns, 'actions' => $actions, 'bulkActions' => $bulkActions, 'tabs' => $tabs, 'state' => $state] = $listing;
    $tabs = collect($tabs)->filter(fn (Tab $tab, int $index): bool => $index === 0 || $tab->name === $state['tab'] || $state['counts'][$tab->name] > 0);
    $hideableColumns = collect($columns)->filter(fn (Column $column): bool => $column->isHideable());
    $filterableColumns = collect($columns)->filter(fn (Column $column): bool => $column->isFilterable());
    $isSearchable = collect($columns)->contains(fn (Column $column): bool => $column->isSearchable());
    $columns = collect($columns)->reject(fn (Column $column): bool => in_array($column->name, $state['hiddenColumns'], true));
    $isSelectable = collect($actions)->contains(fn (Action $action): bool => $action->isBulk());
    $confirmations = collect($bulkActions)->mapWithKeys(fn (Action $action): array => [$action->name => $action->getConfirmation()])->filter();
    $keys = collect($records->items())->keys()->map(fn (int|string $key): string => (string) $key);
    $window = UrlWindow::make($records);
    $pages = array_filter([
        array_keys($window['first'] ?? []),
        array_keys($window['slider'] ?? []),
        array_keys($window['last'] ?? []),
    ]);
@endphp

<div {{ $attributes->twMerge('flex flex-col gap-4') }}>
    <div class="flex flex-wrap items-center justify-between gap-2">
        @if ($tabs->isNotEmpty())
            <x-ui.tabs wire:key="listing-tabs-{{ $state['tab'] }}" :value="$state['tab']">
                <x-ui.tabs-list :aria-label="__('Tabs')">
                    @foreach ($tabs as $tab)
                        <x-ui.tabs-trigger
                            wire:key="listing-tab-{{ $tab->name }}"
                            :value="$tab->name"
                            wire:click="$set('tab', '{{ $tab->name }}')"
                        >
                            {{ $tab->getLabel() }}
                            <x-ui.badge variant="outline" size="sm">{{ $state['counts'][$tab->name] }}</x-ui.badge>
                        </x-ui.tabs-trigger>
                    @endforeach
                </x-ui.tabs-list>
            </x-ui.tabs>
        @endif
        <div class="flex w-full items-center gap-2 sm:w-auto">
            @if ($isSearchable)
                <x-ui.input
                    type="search"
                    size="sm"
                    class="w-full sm:w-64"
                    :placeholder="__('Search')"
                    :aria-label="__('Search')"
                    wire:model.live.debounce.300ms="search"
                >
                    <x-slot:leading>
                        <x-lucide-search class="size-4" />
                    </x-slot:leading>
                </x-ui.input>
            @endif

            @foreach ($filterableColumns as $column)
                <x-ui.select
                    wire:key="listing-filter-{{ $column->name }}"
                    class="w-44"
                    :options="['' => __('All')] + $column->getOptions()"
                    :value="$state['filters'][$column->name] ?? ''"
                    :placeholder="$column->getLabel()"
                    wire:model.live="filters.{{ $column->name }}"
                />
            @endforeach

            @if ($hideableColumns->isNotEmpty())
                <x-ui.dropdown-menu>
                    <x-ui.dropdown-menu-trigger>
                        <x-ui.button variant="outline" size="sm" class="ms-auto" :aria-label="__('Columns')">
                            <x-lucide-columns-3 />
                        </x-ui.button>
                    </x-ui.dropdown-menu-trigger>
                    <x-ui.dropdown-menu-content align="end">
                        @foreach ($hideableColumns as $column)
                            <x-ui.dropdown-menu-checkbox-item
                                :checked="! in_array($column->name, $state['hiddenColumns'], true)"
                                wire:click="toggleColumn('{{ $column->name }}')"
                            >
                                {{ $column->getLabel() }}
                            </x-ui.dropdown-menu-checkbox-item>
                        @endforeach
                    </x-ui.dropdown-menu-content>
                </x-ui.dropdown-menu>
            @endif
        </div>
    </div>

    <x-ui.table variant="card" wire:loading.class="opacity-60">
        <x-ui.table-header>
            <x-ui.table-row class="bg-muted/40">
                @if ($state['reorderColumn'])
                    <x-ui.table-head class="w-8 ps-3 pe-0" :aria-sort="$state['isReorderable'] ? 'ascending' : null">
                        <button
                            @class([
                                'hover:text-foreground flex items-center',
                                'text-muted-foreground' => ! $state['isReorderable'],
                            ])
                            title="{{ __('Custom order') }}"
                            aria-label="{{ __('Custom order') }}"
                            wire:click="sortBy({{ Js::from($state['reorderColumn']) }}, 'asc')"
                        >
                            <x-lucide-arrow-down-up class="size-4" />
                        </button>
                    </x-ui.table-head>
                @endif
                @if ($isSelectable)
                    <x-ui.table-head class="w-10 ps-3">
                        <x-ui.checkbox
                            :disabled="$keys->isEmpty()"
                            :aria-label="__('Select all')"
                            x-effect="checked = {{ Js::from($keys) }}.length > 0 && {{ Js::from($keys) }}.every((key) => $wire.selected.includes(key)); indeterminate = ! checked && {{ Js::from($keys) }}.some((key) => $wire.selected.includes(key))"
                            x-on:click="$wire.set('selected', {{ Js::from($keys) }}.every((key) => $wire.selected.includes(key)) ? [] : {{ Js::from($keys) }})"
                        />
                    </x-ui.table-head>
                @endif
                @foreach ($columns as $column)
                    <x-ui.table-head
                        wire:key="listing-head-{{ $column->name }}"
                        :aria-sort="$state['sort'] === $column->name ? ($state['direction'] === 'asc' ? 'ascending' : 'descending') : null"
                    >
                        @if ($column->isSortable())
                            <button
                                type="button"
                                class="hover:text-foreground -ms-1 inline-flex items-center gap-1 rounded px-1"
                                wire:click="sortBy('{{ $column->name }}')"
                            >
                                {{ $column->getLabel() }}
                                @if ($state['sort'] === $column->name)
                                    @if ($state['direction'] === 'asc')
                                        <x-lucide-arrow-up class="size-3.5" />
                                    @else
                                        <x-lucide-arrow-down class="size-3.5" />
                                    @endif
                                @else
                                    <x-lucide-chevrons-up-down class="text-muted-foreground size-3.5" />
                                @endif
                            </button>
                        @else
                            {{ $column->getLabel() }}
                        @endif
                    </x-ui.table-head>
                @endforeach
            </x-ui.table-row>
        </x-ui.table-header>
        <x-ui.table-body :wire:sort="$state['isReorderable'] ? 'reorder' : null">
            @forelse ($records as $key => $record)
                @php($rowActions = array_filter($actions, fn (Action $action): bool => $action->isVisibleFor($record)))
                <x-ui.table-row
                    class="group"
                    wire:key="listing-row-{{ $key }}"
                    :data-state="in_array((string) $key, $state['selected'], true) ? 'selected' : null"
                    :wire:sort:item="$state['isReorderable'] ? $key : null"
                >
                    @if ($state['reorderColumn'])
                        <x-ui.table-cell class="ps-3 pe-0">
                            @if ($state['isReorderable'])
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground flex cursor-grab items-center active:cursor-grabbing"
                                    aria-label="{{ __('Reorder') }}"
                                    wire:sort:handle
                                >
                                    <x-lucide-grip-vertical class="size-4" />
                                </button>
                            @endif
                        </x-ui.table-cell>
                    @endif
                    @if ($isSelectable)
                        <x-ui.table-cell class="ps-3">
                            <x-ui.checkbox
                                :aria-label="__('Select')"
                                x-effect="checked = $wire.selected.includes({{ Js::from((string) $key) }})"
                                x-on:click="$wire.set('selected', $wire.selected.includes({{ Js::from((string) $key) }}) ? $wire.selected.filter((key) => key !== {{ Js::from((string) $key) }}) : [...$wire.selected, {{ Js::from((string) $key) }}])"
                            />
                        </x-ui.table-cell>
                    @endif
                    @foreach ($columns as $column)
                        <x-ui.table-cell
                            wire:key="listing-cell-{{ $key }}-{{ $column->name }}"
                            @class(['font-medium' => $loop->first])
                        >
                            {{ $column->getContent($record) }}
                            @if ($loop->first && $rowActions)
                                <div class="text-muted-foreground mt-1 flex flex-wrap items-center gap-x-1.5 text-xs font-normal sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100">
                                    @foreach ($rowActions as $action)
                                        @if (! $loop->first)
                                            <span aria-hidden="true">|</span>
                                        @endif
                                        <x-ui.button
                                            variant="link"
                                            size="xs"
                                            @class(['h-auto p-0', 'text-destructive' => $action->isDestructive()])
                                            :href="$action->getUrl($record)"
                                            :wire:click="$action->getUrl($record) ? null : 'runAction('.Js::from($action->name).', '.Js::from($key).')'"
                                            :wire:confirm="$action->getConfirmation()"
                                        >
                                            {{ $action->getLabel() }}
                                        </x-ui.button>
                                    @endforeach
                                </div>
                            @endif
                        </x-ui.table-cell>
                    @endforeach
                </x-ui.table-row>
            @empty
                <x-ui.table-row class="hover:bg-transparent">
                    <x-ui.table-cell :colspan="$columns->count() + ($isSelectable ? 1 : 0) + ($state['reorderColumn'] ? 1 : 0)">
                        <x-ui.empty>
                            <x-ui.empty-header>
                                <x-ui.empty-media variant="icon"><x-lucide-search-x /></x-ui.empty-media>
                                <x-ui.empty-title>{{ __('No results') }}</x-ui.empty-title>
                            </x-ui.empty-header>
                        </x-ui.empty>
                    </x-ui.table-cell>
                </x-ui.table-row>
            @endforelse
        </x-ui.table-body>
    </x-ui.table>

    <div class="flex flex-wrap items-center gap-4 text-sm">
        @if ($bulkActions)
            <x-ui.select
                wire:key="listing-bulk-actions-{{ collect($bulkActions)->pluck('name')->implode('-') }}"
                class="flex items-center gap-2"
            >
                <x-ui.select-trigger size="sm" class="w-48" :aria-label="__('Bulk actions')">
                    <x-ui.select-value :placeholder="__('Bulk actions')" />
                </x-ui.select-trigger>
                <x-ui.select-content>
                    @foreach ($bulkActions as $action)
                        <x-ui.select-item :value="$action->name">
                            @if ($action->getIcon())
                                <x-dynamic-component :component="$action->getIcon()" />
                            @endif
                            {{ $action->getLabel() }}
                        </x-ui.select-item>
                    @endforeach
                </x-ui.select-content>
                <x-ui.button
                    variant="outline"
                    size="sm"
                    x-bind:disabled="! value || ! $wire.selected.length"
                    x-on:click="if (! {{ Js::from($confirmations) }}[value] || confirm({{ Js::from($confirmations) }}[value])) $wire.runAction(value).then(() => value = '')"
                >
                    {{ __('Apply') }}
                </x-ui.button>
            </x-ui.select>
        @endif
        <span class="text-muted-foreground"> {{ trans_choice(':count item|:count items', $records->total()) }} </span>
        <div class="ms-auto flex items-center gap-2">
            <span class="text-muted-foreground">{{ __('Per page') }}</span>
            <x-ui.select
                :options="array_combine($state['perPageOptions'], $state['perPageOptions'])"
                wire:model.live="perPage"
            />
        </div>
        @if ($records->hasPages())
            <x-ui.pagination class="mx-0 w-auto">
                <x-ui.pagination-content>
                    <x-ui.pagination-item>
                        <x-ui.pagination-previous
                            :href="$records->previousPageUrl() ?? '#'"
                            :aria-disabled="$records->onFirstPage() ? 'true' : null"
                            @class(['pointer-events-none opacity-50' => $records->onFirstPage()])
                            wire:click.prevent="previousPage"
                        />
                    </x-ui.pagination-item>
                    @foreach ($pages as $group)
                        @if (! $loop->first)
                            <x-ui.pagination-item><x-ui.pagination-ellipsis /></x-ui.pagination-item>
                        @endif
                        @foreach ($group as $page)
                            <x-ui.pagination-item wire:key="listing-page-{{ $page }}">
                                <x-ui.pagination-link
                                    :href="$records->url($page)"
                                    :is-active="$page === $records->currentPage()"
                                    wire:click.prevent="gotoPage({{ $page }})"
                                >
                                    {{ $page }}
                                </x-ui.pagination-link>
                            </x-ui.pagination-item>
                        @endforeach
                    @endforeach
                    <x-ui.pagination-item>
                        <x-ui.pagination-next
                            :href="$records->nextPageUrl() ?? '#'"
                            :aria-disabled="$records->hasMorePages() ? null : 'true'"
                            @class(['pointer-events-none opacity-50' => ! $records->hasMorePages()])
                            wire:click.prevent="nextPage"
                        />
                    </x-ui.pagination-item>
                </x-ui.pagination-content>
            </x-ui.pagination>
        @endif
    </div>
</div>
