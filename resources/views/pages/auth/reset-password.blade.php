<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}" />

            <!-- Email Address -->
            <x-ui.field>
                <x-ui.field-label for="email">{{ __('Email') }}</x-ui.field-label>
                <x-ui.input
                    id="email"
                    name="email"
                    value="{{ request('email') }}"
                    type="email"
                    required
                    autocomplete="email"
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
                <x-ui.button type="submit">{{ __('Reset password') }}</x-ui.button>
            </div>
        </form>
    </div>
</x-layouts::auth>
