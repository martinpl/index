<?php

use App\Facades\Role;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Component;

new class extends Component
{
    use WithListing;

    public function booted(): void
    {
        abort_unless(Gate::allows('users.manage'), 403);
    }

    /**
     * @return Builder<User>
     */
    protected function records(): Builder
    {
        return User::with(['meta' => fn ($query) => $query->where('key', 'roles')])->withCount('entries');
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        return [
            Tab::make('all')->label(__('All')),
            ...Role::list()->map(fn (array $role): Tab => Tab::make($role['key'])
                ->label(__($role['name']))
                ->query(fn (Builder $query) => $query->whereMetaContains('roles', $role['key'])))->values(),
        ];
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('Name'))
                ->sortable()
                ->searchable()
                ->hideable(false)
                ->render(fn (User $user): HtmlString => new HtmlString(
                    '<a href="'.e(route('dashboard.users.edit', $user)).'" wire:navigate class="font-medium hover:underline">'.e($user->name).'</a>'
                    .($user->is(Auth::user()) ? ' <span class="text-muted-foreground">— '.e(__('You')).'</span>' : '')
                )),
            Column::make('email')->label(__('Email'))->sortable()->searchable(),
            Column::make('roles')->label(__('Roles'))->render(fn (User $user): string => collect($user->meta->firstWhere('key', 'roles')?->value)
                ->map(fn (string $role): string => __(Role::get($role)['name'] ?? $role))
                ->join(', ')),
            Column::make('entries_count')->label(__('Entries')),
            Column::make('created_at')->label(__('Registered'))->sortable()->hidden()->date(),
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
                ->url(fn (User $user): string => route('dashboard.users.edit', $user)),
            Action::make('delete')
                ->label(__('Delete'))
                ->icon('lucide-trash-2')
                ->destructive()
                ->bulk()
                ->confirm(__('Are you sure you want to delete the selected users? Their entries will be kept without an author.'))
                ->visible(fn (User $user): bool => ! $user->is(Auth::user()))
                ->handle(fn (Collection $users) => $users->each->delete())
                ->successMessage(fn (Collection $users): string => trans_choice(':count user deleted.|:count users deleted.', $users->count())),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('name');
    }

    public function render(): View
    {
        return $this->view()->title(__('Users'));
    }
};
?>

<x-slot:actions>
    <x-ui.button :href="route('dashboard.users.create')" size="sm" wire:navigate>
        <x-lucide-plus />
        {{ __('Add User') }}
    </x-ui.button>
</x-slot:actions>

<x-listing :$listing />
