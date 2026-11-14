<?php

use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\WithListing;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public function booted(): void
    {
        abort_unless(Site::current()?->isMain() && Gate::allows('sites.manage'), 403);
    }

    /**
     * @return Builder<Site>
     */
    protected function records(): Builder
    {
        return Site::query();
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('Name'))
                ->hideable(false)
                ->render(fn (Site $site): HtmlString => new HtmlString(
                    '<a href="'.e(route('dashboard.sites.edit', $site)).'" wire:navigate class="font-medium hover:underline">'
                    .e($site->setting('site_name', $site->domain)).'</a>'
                    .($site->isMain() ? ' <span class="text-muted-foreground">— '.e(__('Main site')).'</span>' : '')
                )),
            Column::make('domain')->label(__('Domain'))->sortable()->searchable(),
            Column::make('path')->label(__('Path'))->sortable()->searchable(),
            Column::make('id')->label(__('ID'))->sortable()->hidden(),
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
                ->url(fn (Site $site): string => route('dashboard.sites.edit', $site)),
            Action::make('visit')
                ->label(__('Visit'))
                ->icon('lucide-external-link')
                ->url(fn (Site $site): string => $site->url()),
            Action::make('dashboard')
                ->label(__('Dashboard'))
                ->icon('lucide-layout-dashboard')
                ->url(fn (Site $site): string => $site->url('dashboard')),
            Action::make('delete')
                ->label(__('Delete'))
                ->icon('lucide-trash-2')
                ->destructive()
                ->bulk()
                ->confirm(__('Are you sure you want to delete the selected sites? All of their content, users and media will be permanently deleted.'))
                ->visible(fn (Site $site): bool => ! $site->isMain())
                ->handle(fn (Collection $sites) => $sites->each->delete())
                ->successMessage(fn (Collection $sites): string => trans_choice(':count site deleted.|:count sites deleted.', $sites->count())),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('id');
    }

    public function render(): View
    {
        return $this->view()->title(__('Sites'));
    }
};
?>

<x-slot:actions>
    <x-ui.button :href="route('dashboard.sites.create')" size="sm" wire:navigate>
        <x-lucide-plus />
        {{ __('Add Site') }}
    </x-ui.button>
</x-slot:actions>

<x-listing :$listing />
