<?php

use App\Concerns\ProfileValidationRules;
use App\Facades\Role;
use App\Fields\Field;
use App\Fields\Group;
use App\Fields\Text;
use App\Fields\Toggle;
use App\Fields\WithFields;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    use ProfileValidationRules;
    use WithFields;

    /**
     * The user being edited, or null when adding a new one.
     */
    public ?User $record = null;

    public function mount(?string $user = null): void
    {
        $this->record = $user ? User::findOrFail($user) : null;

        $this->fillForm();
    }

    public function booted(): void
    {
        abort_unless(Gate::allows('users.manage'), 403);
    }

    /**
     * Validate and store the user, going on to edit them when they were just added.
     */
    public function save(): void
    {
        $values = $this->validateValues();

        $user = $this->record ?? new User;
        $user->fill(array_filter(Arr::only($values, ['name', 'email', 'password']), fn (?string $value): bool => $value !== null));

        if ($user->exists && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        foreach ($values['roles'] ?? [] as $role => $hasRole) {
            $hasRole ? $user->giveRole($role) : $user->revokeRole($role);
        }

        if ($this->record) {
            $this->fillForm();

            $this->dispatch('toast', type: 'success', title: __('User updated.'));

            return;
        }

        $user->markEmailAsVerified();

        $this->dispatch('toast', type: 'success', title: __('User added.'));

        $this->redirectRoute('dashboard.users.edit', ['user' => $user], navigate: true);
    }

    /**
     * Fill the form with the values of the edited user, or with the defaults for adding a new one.
     */
    protected function fillForm(): void
    {
        $this->fillValues([
            ...$this->record?->only(['name', 'email']) ?? [],
            'roles' => array_fill_keys($this->record?->getRoles() ?? [], true),
        ]);
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        $roles = Role::list();

        return [
            Text::make('name')
                ->label(__('Name'))
                ->rules($this->nameRules()),
            Text::make('email')
                ->label(__('Email'))
                ->type('email')
                ->rules($this->emailRules($this->record?->id)),
            Text::make('password')
                ->label(__('Password'))
                ->type('password')
                ->when(
                    $this->record,
                    fn (Text $field) => $field->description(__('Leave empty to keep the current password.')),
                    fn (Text $field) => $field->required(),
                )
                ->rules('string')
                // The password rule is implicit, so it only applies once a password is entered or required.
                ->when(! $this->record || filled($this->values['password'] ?? null), fn (Text $field) => $field->rules(Password::default())),
            Group::make('roles')
                ->label(__('Roles'))
                ->visible($roles->isNotEmpty())
                ->fields($roles->map(fn (array $role): Toggle => Toggle::make($role['key'])->label(__($role['name'])))->values()->all()),
        ];
    }

    public function render(): View
    {
        return $this->view()->title($this->record ? __('Edit User') : __('Add User'));
    }
};
?>

<form wire:submit="save" class="flex w-full max-w-lg flex-col gap-6">
    <x-fields :$fields :$values />
    <div>
        <x-ui.button type="submit">{{ $record ? __('Update') : __('Add User') }}</x-ui.button>
    </div>
</form>
