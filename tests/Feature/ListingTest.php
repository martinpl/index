<?php

use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Tab;
use App\Listing\WithListing;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Livewire;

class PluginListing extends Component
{
    use WithListing;

    /**
     * @var list<string>
     */
    public static array $activated = [];

    protected function records(): Collection
    {
        return collect([
            'blog' => ['name' => 'Blog', 'status' => 'active'],
            'seo' => ['name' => 'SEO', 'status' => 'inactive'],
            'forms' => ['name' => 'Forms', 'status' => 'inactive'],
        ]);
    }

    protected function columns(): array
    {
        return [Column::make('name')->sortable()->searchable()];
    }

    protected function actions(): array
    {
        return [
            Action::make('activate')
                ->bulk()
                ->visible(fn (array $plugin): bool => $plugin['status'] === 'inactive')
                ->handle(fn (Collection $plugins) => static::$activated = $plugins->keys()->all()),
        ];
    }

    protected function tabs(): array
    {
        return [
            Tab::make('all'),
            Tab::make('active')->query(fn (Collection $plugins): Collection => $plugins->where('status', 'active')),
        ];
    }

    public function render(): string
    {
        return '<x-listing :$listing />';
    }
}

test('lists a collection with tabs, search and sorting', function () {
    Livewire::test(PluginListing::class)
        ->assertSeeInOrder(['All', '3', 'Active', '1'])
        ->assertSeeHtml('wire:key="listing-row-blog"')
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['Blog', 'Forms', 'SEO'])
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['SEO', 'Forms', 'Blog'])
        ->set('search', 'fo')
        ->assertSee('Forms')
        ->assertDontSee(['Blog', 'SEO'])
        ->set('search', '')
        ->set('tab', 'active')
        ->assertSee('Blog')
        ->assertDontSee(['Forms', 'SEO']);
});

test('runs a bulk action on the selected records of a collection it is visible for', function () {
    Livewire::test(PluginListing::class)
        ->set('selected', ['blog', 'seo'])
        ->call('runAction', 'activate')
        ->assertSet('selected', []);

    expect(PluginListing::$activated)->toBe(['seo']);
});
