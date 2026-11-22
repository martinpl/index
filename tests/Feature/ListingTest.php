<?php

use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use App\Models\Setting;
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

class ReorderablePluginListing extends PluginListing
{
    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('order')->reorderable();
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

test('does not reorder the records of a collection, which have no column storing their position', function () {
    Livewire::test(ReorderablePluginListing::class)
        ->assertDontSeeHtml('wire:sort="reorder"')
        ->call('reorder', 'seo', 0)
        ->assertOk()
        ->assertSeeInOrder(['Blog', 'SEO', 'Forms']);
});

test('sorts by a column in the given direction', function () {
    Livewire::test(PluginListing::class)
        ->call('sortBy', 'name', 'desc')
        ->assertSet('direction', 'desc')
        ->assertSeeInOrder(['SEO', 'Forms', 'Blog'])
        ->call('sortBy', 'name', 'desc')
        ->assertSet('direction', 'desc')
        ->call('sortBy', 'name', 'sideways')
        ->assertSet('direction', 'asc')
        ->assertSeeInOrder(['Blog', 'Forms', 'SEO']);
});

test('runs a bulk action on the selected records of a collection it is visible for', function () {
    Livewire::test(PluginListing::class)
        ->set('selected', ['blog', 'seo'])
        ->call('runAction', 'activate')
        ->assertSet('selected', []);

    expect(PluginListing::$activated)->toBe(['seo']);
});

test('date columns show the value in the timezone of the site', function () {
    Setting::set('timezone', 'Europe/Warsaw', 'core');
    $record = ['date' => now()->setDate(2026, 11, 7)->setTime(23, 30)];

    expect(Column::make('date')->date()->getContent($record))->toBe('2026-11-08')
        ->and(Column::make('date')->dateTime()->getContent($record))->toBe('2026-11-08 00:30')
        ->and(Column::make('date')->getContent(['date' => null]))->toBeNull();
});

test('date columns use the date and time formats of the site', function () {
    Setting::set('date_format', 'd.m.Y', 'core');
    Setting::set('time_format', 'g:i A', 'core');
    $record = ['date' => now()->setDate(2026, 11, 7)->setTime(13, 5)];

    expect(Column::make('date')->date()->getContent($record))->toBe('07.11.2026')
        ->and(Column::make('date')->dateTime()->getContent($record))->toBe('07.11.2026 1:05 PM');
});

test('columns link their content and follow it by the non-empty states', function () {
    $column = Column::make('name')
        ->url(fn (array $record): string => "/plugins/{$record['key']}")
        ->states(fn (array $record): array => [when($record['active'], 'Active'), null, 'Plugin']);

    expect((string) $column->getContent(['key' => 'seo', 'name' => '<b>SEO</b>', 'active' => false]))
        ->toBe('<a href="/plugins/seo" wire:navigate class="hover:underline">&lt;b&gt;SEO&lt;/b&gt;</a> <span class="text-muted-foreground">— Plugin</span>')
        ->and(Column::make('name')->getContent(['name' => 'Blog']))->toBe('Blog');
});
