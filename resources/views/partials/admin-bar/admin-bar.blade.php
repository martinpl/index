@auth
    @php($isDashboard = request()->routeIs('dashboard.*', 'profile.*', 'security.*'))
    @if (! $isDashboard)
        @php(\Livewire\Livewire::forceAssetInjection())
        @vite(['resources/css/app.css', 'resources/js/head.js'])
    @endif
    <x-ui.menubar id="admin-bar" class="sticky top-0 z-50 h-10 rounded-none border-x-0 border-t-0 px-2">
        @php($menus = \App\Facades\AdminBar::tree())
        @foreach (['start', 'end'] as $align)
            <div @class(['flex items-center gap-1', 'ms-auto' => $align === 'end'])>
                @foreach ($menus->where('align', $align) as $menu)
                    @if ($menu['children']->isEmpty())
                        <a
                            href="{{ $menu['url'] }}"
                            class="hover:bg-accent hover:text-accent-foreground [&_svg]:size-4 flex items-center gap-2 rounded-sm px-2 py-1 text-sm font-medium"
                        >
                            @if ($menu['icon'])
                                <x-dynamic-component :component="$menu['icon']" />
                            @endif
                            {{ $menu['title'] }}
                        </a>
                    @else
                        <x-ui.menubar-menu>
                            <x-ui.menubar-trigger class="[&_svg]:size-4 gap-2">
                                @if ($menu['icon'])
                                    <x-dynamic-component :component="$menu['icon']" />
                                @endif
                                {{ $menu['title'] }}
                            </x-ui.menubar-trigger>
                            <x-ui.menubar-content :align="$align" @class(['min-w-56' => $align === 'end'])>
                                @foreach ($menu['children'] as $item)
                                    @if ($item['title'] instanceof \Illuminate\Contracts\Support\Htmlable)
                                        {{ $item['title'] }}
                                    @else
                                        <x-ui.menubar-item :href="$item['url']">
                                            @if ($item['icon'])
                                                <x-dynamic-component :component="$item['icon']" />
                                            @endif
                                            {{ $item['title'] }}
                                        </x-ui.menubar-item>
                                    @endif
                                @endforeach
                            </x-ui.menubar-content>
                        </x-ui.menubar-menu>
                    @endif
                @endforeach
            </div>
        @endforeach
    </x-ui.menubar>
@endauth
