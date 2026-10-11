<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <x-ui.field>
                <x-ui.field-label for="name">{{ __('Name') }}</x-ui.field-label>
                <x-ui.input
                    id="name"
                    name="name"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Full name')"
                />
                <x-ui.field-error :messages="$errors->get('name')" />
            </x-ui.field>

            <!-- Email Address -->
            <x-ui.field>
                <x-ui.field-label for="email">{{ __('Email address') }}</x-ui.field-label>
                <x-ui.input
                    id="email"
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <x-ui.field-error :messages="$errors->get('email')" />
            </x-ui.field>

            <!-- Password -->
            <x-ui.field>
                <x-ui.field-label for="password">{{ __('Password') }}</x-ui.field-label>
                <x-ui.input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />
                <x-ui.field-error :messages="$errors->get('password')" />
            </x-ui.field>

            <!-- Confirm Password -->
            <x-ui.field>
                <x-ui.field-label for="password_confirmation">{{ __('Confirm password') }}</x-ui.field-label>
                <x-ui.input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />
                <x-ui.field-error :messages="$errors->get('password_confirmation')" />
            </x-ui.field>

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </x-ui.button>
            </div>
        </form>

        <div class="text-muted-foreground space-x-1 text-center text-sm rtl:space-x-reverse">
            <span>{{ __('Already have an account?') }}</span>
            <x-ui.link :href="route('login')" wire:navigate>{{ __('Log in') }}</x-ui.link>
        </div>
    </div>
</x-layouts::auth>
