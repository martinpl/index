<?php

use App\Facades\Theme;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\WithListing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public function boot(): void
    {
        abort_unless(Gate::allows('themes.manage'), 403);
    }

    /**
     * @return Collection<string, array{slug: string, name: string, version: string|null, description: string|null, status: string}>
     */
    protected function records(): Collection
    {
        return Theme::list();
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('Theme'))
                ->sortable()
                ->searchable()
                ->hideable(false)
                ->render(fn (array $theme): HtmlString => new HtmlString(
                    '<span class="font-medium">'.e($theme['name']).'</span>'
                    .($theme['status'] === 'active' ? ' <span class="text-muted-foreground">— '.e(__('Active')).'</span>' : '')
                )),
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
                ->visible(fn (array $theme): bool => $theme['status'] === 'inactive')
                ->handle(fn (Collection $themes) => Theme::activate($themes->keys()->first()))
                ->successMessage(fn (Collection $themes): string => __(':theme activated.', ['theme' => $themes->first()['name']])),
            Action::make('deactivate')
                ->label(__('Deactivate'))
                ->icon('lucide-power-off')
                ->confirm(__('Are you sure you want to deactivate the theme? Without an active theme the site runs headless, serving no frontend.'))
                ->visible(fn (array $theme): bool => $theme['status'] === 'active')
                ->handle(fn (Collection $themes) => Theme::deactivate($themes->keys()->first()))
                ->successMessage(fn (Collection $themes): string => __(':theme deactivated.', ['theme' => $themes->first()['name']])),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('name');
    }

    public function render(): View
    {
        return $this->view()->title(__('Themes'));
    }
};
?>

<x-listing :$listing />
