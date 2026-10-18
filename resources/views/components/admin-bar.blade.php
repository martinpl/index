@auth
    @php($user = auth()->user())
    @php($isDashboard = request()->routeIs('dashboard.*', 'profile.*', 'security.*'))
    @if (! $isDashboard)
        @php(\Livewire\Livewire::forceAssetInjection())
        @vite(['resources/css/app.css', 'resources/js/head.js'])
    @endif
    <div>
        <x-ui.menubar id="admin-bar" class="sticky top-0 z-50 h-10 rounded-none border-x-0 border-t-0 px-2">
            <x-ui.menubar-menu>
                <x-ui.menubar-trigger class="gap-2">
                    <x-app-logo-icon class="size-4" />
                    {{ option('site_name') }}
                </x-ui.menubar-trigger>
                <x-ui.menubar-content>
                    @if (! $isDashboard)
                        <x-ui.menubar-item :href="route('dashboard.home')" wire:navigate>
                            <x-lucide-layout-dashboard />
                            {{ __('Dashboard') }}
                        </x-ui.menubar-item>
                    @else
                        <x-ui.menubar-item :href="page_url('home')" wire:navigate>
                            <x-lucide-home />
                            {{ __('Home') }}
                        </x-ui.menubar-item>
                    @endif
                </x-ui.menubar-content>
            </x-ui.menubar-menu>

            <x-ui.menubar-menu class="ms-auto">
                <x-ui.menubar-trigger class="gap-2">
                    {{ $user->name }}
                    <x-ui.avatar class="size-6 rounded-sm">
                        <x-ui.avatar-fallback class="rounded-sm text-xs">{{ $user->initials() }}</x-ui.avatar-fallback>
                    </x-ui.avatar>
                </x-ui.menubar-trigger>
                <x-ui.menubar-content align="end" class="min-w-56">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <x-ui.avatar class="rounded-lg">
                            <x-ui.avatar-fallback class="rounded-lg">{{ $user->initials() }}</x-ui.avatar-fallback>
                        </x-ui.avatar>
                        <x-ui.menubar-label class="p-0 font-normal">
                            <div class="grid text-start text-sm leading-tight">
                                <span class="truncate font-medium">{{ $user->name }}</span>
                                <span class="text-muted-foreground truncate text-xs">{{ $user->email }}</span>
                            </div>
                        </x-ui.menubar-label>
                    </div>
                    <x-ui.menubar-separator />
                    <x-ui.menubar-item :href="route('profile.edit')" wire:navigate>
                        <x-lucide-settings />
                        {{ __('Settings') }}
                    </x-ui.menubar-item>
                    <x-ui.menubar-separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.menubar-item x-on:click="$el.form.submit()">
                            <x-lucide-log-out />
                            {{ __('Log out') }}
                        </x-ui.menubar-item>
                    </form>
                </x-ui.menubar-content>
            </x-ui.menubar-menu>
        </x-ui.menubar>

@endauth
