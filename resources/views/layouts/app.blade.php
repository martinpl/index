<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @action('dashboard.head')
</head>
<body class="min-h-screen antialiased">
    @action('dashboard.body.open')
    @php($hasAdminbar = true)
    <x-ui.sidebar-provider @class(['[&>div>div]:h-auto min-h-[calc(100svh-40px)] transform-gpu' => $hasAdminbar])>
        <x-ui.sidebar>
            <x-ui.sidebar-header class="border-sidebar-border h-16 flex-row items-center border-b">
                <x-app-logo :sidebar="true" href="{{ route('dashboard.home') }}" class="min-w-0 flex-1" />
                @if (\App\Models\Site::current()?->isMain() && auth()->user()->can('sites.manage'))
                    <x-ui.button
                        :href="route('dashboard.sites')"
                        wire:navigate
                        :variant="request()->routeIs('dashboard.sites', 'dashboard.sites.*') ? 'secondary' : 'ghost'"
                        size="icon"
                        aria-label="{{ __('Sites') }}"
                        title="{{ __('Sites') }}"
                    >
                        <x-lucide-network />
                    </x-ui.button>
                @endif
            </x-ui.sidebar-header>
            <x-ui.sidebar-content>
                <x-ui.sidebar-group>
                    <x-ui.sidebar-group-content class="flex flex-col gap-2">
                        <x-ui.sidebar-menu>
                            @foreach (\App\Facades\DashboardMenu::tree() as $item)
                                {{-- A single child linking to the page of its parent, like the only settings page, adds nothing. --}}
                                @php($children = $item['children']->count() === 1 && $item['children']->first()['url'] === $item['url'] ? collect() : $item['children'])
                                {{-- The current item shows its children, the others only while focused or hovered for a moment, or once tapped on mobile, sliding them open. --}}
                                @php($isCollapsed = $children->isNotEmpty() && ! ($item['active'] || $children->contains('active', true)))
                                <x-ui.sidebar-menu-item
                                    x-data="{ expanded: false }"
                                    x-bind:data-expanded="expanded ? '' : null"
                                >
                                    {{-- With no hover on mobile, a first tap opens the children of a collapsed item and a second follows its link. --}}
                                    <x-ui.sidebar-menu-button
                                        :href="$item['url']"
                                        :is-active="$item['active']"
                                        x-on:click="if ({{ Js::from($isCollapsed) }} && isMobile && ! expanded) { $event.preventDefault(); expanded = true; }"
                                    >
                                        @if ($item['icon'])
                                            <x-dynamic-component :component="$item['icon']" />
                                        @endif
                                        <span>{{ $item['title'] }}</span>
                                    </x-ui.sidebar-menu-button>
                                    @if ($children->isNotEmpty())
                                        <div @class([
                                            'grid',
                                            'invisible grid-rows-[0fr] opacity-0 transition-[grid-template-rows,opacity,visibility] duration-200 ease-out group-focus-within/menu-item:visible group-focus-within/menu-item:grid-rows-[1fr] group-focus-within/menu-item:opacity-100 group-hover/menu-item:visible group-hover/menu-item:grid-rows-[1fr] group-hover/menu-item:opacity-100 group-hover/menu-item:delay-250 group-data-expanded/menu-item:visible group-data-expanded/menu-item:grid-rows-[1fr] group-data-expanded/menu-item:opacity-100 motion-reduce:transition-none' => $isCollapsed,
                                        ])>
                                            <div class="min-h-0 overflow-hidden">
                                                <x-ui.sidebar-menu-sub>
                                                    @foreach ($children as $child)
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
                                            </div>
                                        </div>
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
                <div id="actions" class="flex flex-1 items-center justify-end gap-2">{{ $actions ?? '' }}</div>
            </header>
            <div class="flex flex-1 flex-col gap-4 p-4">{{ $slot }}</div>
        </x-ui.sidebar-inset>
    </x-ui.sidebar-provider>
    @persist('toast')
        <x-ui.sonner />
    @endpersist
</body>
</html>
