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
                <x-app-logo :sidebar="true" href="{{ route('dashboard.home') }}" />
            </x-ui.sidebar-header>
            <x-ui.sidebar-content>
                <x-ui.sidebar-group>
                    <x-ui.sidebar-group-content class="flex flex-col gap-2">
                        <x-ui.sidebar-menu>
                            @foreach (\App\Facades\DashboardMenu::tree() as $item)
                                <x-ui.sidebar-menu-item>
                                    <x-ui.sidebar-menu-button :href="$item['url']" :is-active="$item['active']">
                                        @if ($item['icon'])
                                            <x-dynamic-component :component="$item['icon']" />
                                        @endif
                                        <span>{{ $item['title'] }}</span>
                                    </x-ui.sidebar-menu-button>
                                    @if ($item['children']->isNotEmpty())
                                        <x-ui.sidebar-menu-sub>
                                            @foreach ($item['children'] as $child)
                                                <x-ui.sidebar-menu-sub-item>
                                                    <x-ui.sidebar-menu-sub-button
                                                        :href="$child['url']"
                                                        :is-active="$child['active']"
                                                    >
                                                        <span>{{ $child['title'] }}</span>
                                                    </x-ui.sidebar-menu-sub-button>
                                                </x-ui.sidebar-menu-sub-item>
                                            @endforeach
                                        </x-ui.sidebar-menu-sub>
                                    @endif
                                </x-ui.sidebar-menu-item>
                            @endforeach
                        </x-ui.sidebar-menu>
                    </x-ui.sidebar-group-content>
                </x-ui.sidebar-group>
            </x-ui.sidebar-content>
        </x-ui.sidebar>
        <x-ui.sidebar-inset>
            <header class="bg-background sticky top-10 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4">
                <x-ui.sidebar-trigger class="-ml-1" />
                <h1 class="text-base font-medium">{{ $title }}</h1>
                <div id="actions" class="flex flex-1 items-center gap-2">{{ $actions ?? '' }}</div>
            </header>
            <div class="flex flex-1 flex-col gap-4 p-4">{{ $slot }}</div>
        </x-ui.sidebar-inset>
    </x-ui.sidebar-provider>
    @persist('toast')
        <x-ui.sonner />
    @endpersist
</body>
</html>
