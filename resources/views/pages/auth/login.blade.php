<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Log in to your account')"
            :description="__('Enter your email and password below to log in')"
        />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.field>
                <x-ui.field-label for="email">{{ __('Email address') }}</x-ui.field-label>
                <x-ui.input
                    id="email"
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <x-ui.field-error :messages="$errors->get('email')" />
            </x-ui.field>

            <!-- Password -->
            <x-ui.field>
                <div class="flex items-center">
                    <x-ui.field-label for="password">{{ __('Password') }}</x-ui.field-label>

                    @if (Route::has('password.request'))
                        <x-ui.link class="ms-auto text-sm" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </x-ui.link>
                    @endif
                </div>
                <x-ui.input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                />
                <x-ui.field-error :messages="$errors->get('password')" />
            </x-ui.field>

            <!-- Remember Me -->
            <x-ui.field orientation="horizontal">
                <x-ui.checkbox id="remember" name="remember" :checked="old('remember')" />
                <x-ui.field-label for="remember">{{ __('Remember me') }}</x-ui.field-label>
            </x-ui.field>

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" class="w-full" data-test="login-button">{{ __('Log in') }}</x-ui.button>
            </div>
        </form>

        <div class="text-muted-foreground space-x-1 text-center text-sm rtl:space-x-reverse">
            <span>{{ __('Don\'t have an account?') }}</span>
            <x-ui.link :href="route('register')" wire:navigate>{{ __('Sign up') }}</x-ui.link>
        </div>
    </div>
</x-layouts::auth>
