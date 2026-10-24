<?php

use App\Facades\Plugin;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public function boot(): void
    {
        abort_unless(Gate::allows('plugins.manage'), 403);
    }

    /**
     * @return Collection<string, array{slug: string, name: string, version: string|null, description: string|null, status: string}>
     */
    protected function records(): Collection
    {
        return Plugin::list();
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        return [
            Tab::make('all')->label(__('All')),
            Tab::make('active')->label(__('Active'))->query(fn (Collection $plugins): Collection => $plugins->where('status', 'active')),
            Tab::make('inactive')->label(__('Inactive'))->query(fn (Collection $plugins): Collection => $plugins->where('status', 'inactive')),
        ];
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')->label(__('Plugin'))->sortable()->searchable()->hideable(false),
            Column::make('description')->label(__('Description'))->searchable(),
            Column::make('version')->label(__('Version')),
        ];
    }

    /**
     * @return list<Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('activate')
                ->label(__('Activate'))
                ->icon('lucide-power')
                ->bulk()
                ->visible(fn (array $plugin): bool => $plugin['status'] === 'inactive')
                ->handle(fn (Collection $plugins) => $plugins->keys()->each(fn (string $slug) => Plugin::activate($slug)))
                ->successMessage(fn (Collection $plugins): string => trans_choice(':count plugin activated.|:count plugins activated.', $plugins->count())),
            Action::make('deactivate')
                ->label(__('Deactivate'))
                ->icon('lucide-power-off')
                ->bulk()
                ->visible(fn (array $plugin): bool => $plugin['status'] === 'active')
                ->handle(fn (Collection $plugins) => $plugins->keys()->each(fn (string $slug) => Plugin::deactivate($slug)))
                ->successMessage(fn (Collection $plugins): string => trans_choice(':count plugin deactivated.|:count plugins deactivated.', $plugins->count())),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('name');
    }

    public function render(): View
    {
        return $this->view()->title(__('Plugins'));
    }
};
?>

<x-listing :$listing />
