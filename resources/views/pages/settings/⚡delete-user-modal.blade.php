<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-ui.dialog id="confirm-user-deletion" :open="$errors->isNotEmpty()">
    <x-ui.dialog-content class="sm:max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ __('Are you sure you want to delete your account?') }}</x-ui.dialog-title>

                <x-ui.dialog-description>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </x-ui.dialog-description>
            </x-ui.dialog-header>

            <x-ui.field>
                <x-ui.field-label for="delete-user-password">{{ __('Password') }}</x-ui.field-label>
                <x-ui.input id="delete-user-password" wire:model="password" type="password" />
                <x-ui.field-error :messages="$errors->get('password')" />
            </x-ui.field>

            <x-ui.dialog-footer>
                <x-ui.dialog-close>
                    <x-ui.button variant="secondary">{{ __('Cancel') }}</x-ui.button>
                </x-ui.dialog-close>

                <x-ui.button variant="destructive" type="submit">{{ __('Delete account') }}</x-ui.button>
            </x-ui.dialog-footer>
        </form>
    </x-ui.dialog-content>
</x-ui.dialog>
