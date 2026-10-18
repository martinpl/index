<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <x-ui.typography variant="muted" class="text-center">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </x-ui.typography>

        @if (session('status') == 'verification-link-sent')
            <p class="text-success text-center text-sm font-medium">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit">{{ __('Resend verification email') }}</x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button variant="ghost" type="submit" class="cursor-pointer text-sm">
                    {{ __('Log out') }}
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts::auth>
