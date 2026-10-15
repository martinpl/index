<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();

        $this->dispatch('close-dialog-two-factor-setup-modal');
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}; ?>

<x-ui.dialog id="two-factor-setup-modal" x-init="$watch('open', (value) => value || $wire.closeModal())">
    <x-ui.dialog-content class="sm:max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="bg-card w-auto rounded-full border p-0.5 shadow-sm">
                    <div class="bg-muted relative overflow-hidden rounded-full border p-2.5">
                        <div class="[&>div]:flex-1 absolute inset-0 flex h-full w-full items-stretch justify-around divide-x opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="[&>div]:flex-1 absolute inset-0 flex h-full w-full flex-col items-stretch justify-around divide-y opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <x-lucide-qr-code class="relative z-20 size-6" />
                    </div>
                </div>

                <x-ui.dialog-header class="text-center sm:text-center">
                    <x-ui.dialog-title>{{ $this->modalConfig['title'] }}</x-ui.dialog-title>
                    <x-ui.dialog-description>{{ $this->modalConfig['description'] }}</x-ui.dialog-description>
                </x-ui.dialog-header>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div
                        class="flex flex-col items-center justify-center space-y-3"
                        x-data
                        x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                    >
                        <x-ui.input-otp name="code" wire:model="code" :maxlength="6" :aria-label="__('OTP Code')">
                            <x-ui.input-otp-group>
                                @for ($i = 0; $i < 6; $i++)
                                    <x-ui.input-otp-slot :index="$i" />
                                @endfor
                            </x-ui.input-otp-group>
                        </x-ui.input-otp>

                        <x-ui.field-error :messages="$errors->get('code')" />
                    </div>

                    <div class="flex items-center space-x-3">
                        <x-ui.button variant="outline" class="flex-1" wire:click="resetVerification">
                            {{ __('Back') }}
                        </x-ui.button>

                        <x-ui.button
                            class="flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                        >
                            {{ __('Confirm') }}
                        </x-ui.button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <x-ui.alert variant="destructive">
                        <x-lucide-circle-x />
                        <x-ui.alert-title>{{ $message }}</x-ui.alert-title>
                    </x-ui.alert>
                @enderror

                <div class="flex justify-center">
                    <div class="relative aspect-square w-64 overflow-hidden rounded-lg border">
                        @empty($qrCodeSvg)
                            <div class="bg-muted absolute inset-0 flex animate-pulse items-center justify-center">
                                <x-ui.spinner />
                            </div>
                        @else
                            <div x-data class="flex h-full items-center justify-center p-4">
                                <div
                                    class="rounded bg-white p-3"
                                    :style="$store.theme.isDark ? 'filter: invert(1) brightness(1.5)' : ''"
                                >
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <x-ui.button
                        :disabled="$errors->has('setupData')"
                        class="w-full"
                        wire:click="showVerificationIfNecessary"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </x-ui.button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex w-full items-center justify-center">
                        <x-ui.separator class="absolute inset-x-0 top-1/2" />
                        <span class="bg-background text-muted-foreground relative px-2 text-sm">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex w-full items-stretch rounded-xl border">
                            @empty($manualSetupKey)
                                <div class="bg-muted flex w-full items-center justify-center p-3">
                                    <x-ui.spinner />
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full bg-transparent p-3 outline-none"
                                />

                                <button
                                    type="button"
                                    @click="copy()"
                                    :aria-label="@js(__('Copy'))"
                                    class="cursor-pointer border-l px-3 transition-colors"
                                >
                                    <x-lucide-copy x-show="! copied" class="size-5" />
                                    <x-lucide-check x-show="copied" class="text-success size-5" />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-ui.dialog-content>
</x-ui.dialog>
