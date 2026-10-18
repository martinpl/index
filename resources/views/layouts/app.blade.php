<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen antialiased">
    @action('dashboard.body')
    @php($hasAdminbar = true)
    <x-ui.sidebar-provider @class(['[&>div>div]:h-auto min-h-[calc(100svh-40px)] transform-gpu' => $hasAdminbar])>
        <x-ui.sidebar>
            <x-ui.sidebar-header class="border-sidebar-border h-16 border-b">
                <x-app-logo :sidebar="true" href="{{ route('dashboard.home') }}" wire:navigate />
            </x-ui.sidebar-header>
            <x-ui.sidebar-content>
                <x-ui.sidebar-group>
                    <x-ui.sidebar-group-content class="flex flex-col gap-2">
                        <x-ui.sidebar-menu>
                            <x-ui.sidebar-menu-item>
                                <x-ui.sidebar-menu-button
                                    :href="route('dashboard.home')"
                                    :is-active="request()->routeIs('dashboard.home')"
                                    wire:navigate
                                >
                                    <x-lucide-layout-dashboard />
                                    <span>{{ __('Dashboard') }}</span>
                                </x-ui.sidebar-menu-button>
                            </x-ui.sidebar-menu-item>
                        </x-ui.sidebar-menu>
                    </x-ui.sidebar-group-content>
                </x-ui.sidebar-group>
            </x-ui.sidebar-content>
        </x-ui.sidebar>
        <x-ui.sidebar-inset>
            <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4">
                <x-ui.sidebar-trigger class="-ml-1" />
                <h1 class="text-base font-medium">{{ $title }}</h1>
            </header>
            <div class="flex flex-1 flex-col gap-4 p-4">{{ $slot }}</div>
        </x-ui.sidebar-inset>
    </x-ui.sidebar-provider>
    @persist('toast')
        <x-ui.sonner />
    @endpersist
</body>
</html>
