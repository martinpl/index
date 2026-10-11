<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('toast', type: 'success', title: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-ui.field>
                <x-ui.field-label for="current_password">{{ __('Current password') }}</x-ui.field-label>
                <x-ui.input
                    id="current_password"
                    wire:model="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                />
                <x-ui.field-error :messages="$errors->get('current_password')" />
            </x-ui.field>
            <x-ui.field>
                <x-ui.field-label for="password">{{ __('New password') }}</x-ui.field-label>
                <x-ui.input
                    id="password"
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />
                <x-ui.field-error :messages="$errors->get('password')" />
            </x-ui.field>
            <x-ui.field>
                <x-ui.field-label for="password_confirmation">{{ __('Confirm password') }}</x-ui.field-label>
                <x-ui.input
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />
                <x-ui.field-error :messages="$errors->get('password_confirmation')" />
            </x-ui.field>

            <div class="flex items-center gap-4">
                <x-ui.button type="submit" data-test="update-password-button">
                    {{ __('Save') }}
                </x-ui.button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h3 class="font-medium">{{ __('Two-factor authentication') }}</h3>
                <x-ui.typography variant="muted">{{ __('Manage your two-factor authentication settings') }}</x-ui.typography>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p>
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <x-ui.button
                                    variant="destructive"
                                    wire:click="disable"
                                >
                                    {{ __('Disable 2FA') }}
                                </x-ui.button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <x-ui.typography variant="muted">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </x-ui.typography>

                            <x-ui.dialog-trigger for="two-factor-setup-modal">
                                <x-ui.button wire:click="$dispatch('start-two-factor-setup')">
                                    {{ __('Enable 2FA') }}
                                </x-ui.button>
                            </x-ui.dialog-trigger>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12">
                <h3 class="font-medium">{{ __('Passkeys') }}</h3>
                <x-ui.typography variant="muted">{{ __('Manage your passkeys for passwordless sign-in') }}</x-ui.typography>

                <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    <div class="overflow-hidden rounded-lg border">
                        @forelse ($passkeys as $passkey)
                            <div @class(['flex items-center justify-between p-4', 'border-b' => ! $loop->last])>
                                <div class="flex items-center gap-4">
                                    <div class="bg-muted flex size-10 shrink-0 items-center justify-center rounded-xl">
                                        <x-lucide-key-round class="text-muted-foreground size-5" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2.5">
                                            <p class="font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <x-ui.badge variant="secondary" size="sm">{{ $passkey['authenticator'] }}</x-ui.badge>
                                            @endif
                                        </div>
                                        <p class="text-muted-foreground text-xs">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="opacity-50 mx-1">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-ui.button
                                    variant="ghost"
                                    size="icon-sm"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    :aria-label="__('Remove passkey')"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                >
                                    <x-lucide-trash-2 />
                                </x-ui.button>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="bg-muted mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl">
                                    <x-lucide-key-round class="text-muted-foreground size-7" />
                                </div>
                                <p class="font-medium">{{ __('No passkeys yet') }}</p>
                                <x-ui.typography variant="muted" class="mt-1">{{ __('Add a passkey to sign in without a password') }}</x-ui.typography>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    <x-ui.dialog
        x-modelable="open"
        wire:model="showDeleteModal"
        x-init="$watch('open', (value) => value || $wire.deletingPasskeyId === null || $wire.closeDeleteModal())"
    >
        <x-ui.dialog-content class="sm:max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ __('Remove passkey') }}</x-ui.dialog-title>
                <x-ui.dialog-description>
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </x-ui.dialog-description>
            </x-ui.dialog-header>

            <x-ui.dialog-footer>
                <x-ui.button
                    variant="outline"
                    wire:click="closeDeleteModal"
                >
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button
                    variant="destructive"
                    wire:click="deletePasskey"
                >
                    {{ __('Remove passkey') }}
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</section>
