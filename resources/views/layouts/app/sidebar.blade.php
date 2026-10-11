<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        <x-ui.sidebar-provider>
            <x-ui.sidebar>
                <x-ui.sidebar-header>
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </x-ui.sidebar-header>

                <x-ui.sidebar-content>
                    <x-ui.sidebar-group>
                        <x-ui.sidebar-group-label>{{ __('Platform') }}</x-ui.sidebar-group-label>
                        <x-ui.sidebar-menu>
                            <x-ui.sidebar-menu-item>
                                <x-ui.sidebar-menu-button :href="route('dashboard')" :is-active="request()->routeIs('dashboard')" wire:navigate>
                                    <x-lucide-house />
                                    <span>{{ __('Dashboard') }}</span>
                                </x-ui.sidebar-menu-button>
                            </x-ui.sidebar-menu-item>
                        </x-ui.sidebar-menu>
                    </x-ui.sidebar-group>

                    <x-ui.sidebar-group class="mt-auto">
                        <x-ui.sidebar-menu>
                            <x-ui.sidebar-menu-item>
                                <x-ui.sidebar-menu-button href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                                    <x-lucide-folder-git-2 />
                                    <span>{{ __('Repository') }}</span>
                                </x-ui.sidebar-menu-button>
                            </x-ui.sidebar-menu-item>
                            <x-ui.sidebar-menu-item>
                                <x-ui.sidebar-menu-button href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                                    <x-lucide-book-open-text />
                                    <span>{{ __('Documentation') }}</span>
                                </x-ui.sidebar-menu-button>
                            </x-ui.sidebar-menu-item>
                        </x-ui.sidebar-menu>
                    </x-ui.sidebar-group>
                </x-ui.sidebar-content>

                <x-ui.sidebar-footer>
                    <x-ui.sidebar-menu>
                        <x-ui.sidebar-menu-item>
                            <x-desktop-user-menu />
                        </x-ui.sidebar-menu-item>
                    </x-ui.sidebar-menu>
                </x-ui.sidebar-footer>
            </x-ui.sidebar>

            <x-ui.sidebar-inset>
                <header class="flex h-14 items-center gap-2 border-b px-4 md:hidden">
                    <x-ui.sidebar-trigger />
                    <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                </header>

                {{ $slot }}
            </x-ui.sidebar-inset>
        </x-ui.sidebar-provider>

        @persist('toast')
            <x-ui.sonner />
        @endpersist
    </body>
</html>
