<?php

use App\Actions\InstallSite;
use App\Concerns\ProfileValidationRules;
use App\Concerns\SiteValidationRules;
use App\Fields\Field;
use App\Fields\Group;
use App\Fields\Text;
use App\Fields\WithFields;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    use ProfileValidationRules;
    use SiteValidationRules;
    use WithFields;

    /**
     * The site being edited, or null when adding a new one.
     */
    public ?Site $record = null;

    public function mount(?string $site = null): void
    {
        $this->record = $site ? Site::findOrFail($site) : null;

        $this->fillForm();
    }

    public function booted(): void
    {
        abort_unless(Site::current()?->isMain() && Gate::allows('sites.manage'), 403);
    }

    /**
     * Validate and store the site, installing a new one with its administrator and going on to edit it.
     */
    public function save(InstallSite $installSite): void
    {
        $values = $this->validateValues();

        if ($this->record) {
            $this->record->update(Arr::only($values, ['domain', 'path']));

            $this->fillForm();

            $this->dispatch('toast', type: 'success', title: __('Site updated.'));

            return;
        }

        $site = Site::create(Arr::only($values, ['domain', 'path']));

        $installSite($site, $values['name'], $values['admin']);

        $this->dispatch('toast', type: 'success', title: __('Site added.'));

        $this->redirectRoute('dashboard.sites.edit', ['site' => $site], navigate: true);
    }

    /**
     * Fill the form with the values of the edited site, or with the defaults for adding a new one,
     * offering the current user as its administrator.
     */
    protected function fillForm(): void
    {
        $this->fillValues($this->record?->only(['domain', 'path']) ?? [
            'path' => '/',
            'admin' => Auth::user()->only(['name', 'email']),
        ]);
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        return [
            Text::make('domain')
                ->label(__('Domain'))
                ->placeholder('example.com')
                ->rules($this->domainRules($this->values['path'] ?? null, $this->record?->id)),
            Text::make('path')
                ->label(__('Path'))
                ->description(__('Serve the site under a path of the domain, like /blog, or use / for the whole domain.'))
                ->rules($this->pathRules()),
            Text::make('name')
                ->label(__('Name'))
                ->visible(! $this->record)
                ->rules('required', 'string', 'max:255'),
            Group::make('admin')
                ->label(__('Administrator'))
                ->description(__('Users are separate for each site, so the site gets its own administrator account.'))
                ->visible(! $this->record)
                ->fields([
                    Text::make('name')
                        ->label(__('Name'))
                        ->rules($this->nameRules()),
                    Text::make('email')
                        ->label(__('Email'))
                        ->type('email')
                        ->rules('required', 'string', 'email', 'max:255'),
                    Text::make('password')
                        ->label(__('Password'))
                        ->type('password')
                        ->required()
                        ->rules('string', Password::default()),
                ]),
        ];
    }

    public function render(): View
    {
        return $this->view()->title($this->record ? __('Edit Site') : __('Add Site'));
    }
};
?>

<form wire:submit="save" class="flex w-full max-w-lg flex-col gap-6">
    <x-fields :$fields :$values />
    <div>
        <x-ui.button type="submit">{{ $record ? __('Update') : __('Add Site') }}</x-ui.button>
    </div>
</form>
