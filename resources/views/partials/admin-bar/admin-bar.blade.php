@auth
    @php($isDashboard = request()->routeIs('dashboard.*', 'profile.*', 'security.*'))
    @if (! $isDashboard)
        @php(\Livewire\Livewire::forceAssetInjection())
        @vite(['resources/css/app.css', 'resources/js/head.js'])
    @endif
    <x-ui.menubar
        id="admin-bar"
        data-admin-bar
        class="text-foreground sticky top-0 z-50 h-10 rounded-none border-x-0 border-t-0 px-2 font-sans text-base/normal shadow-none"
    >
        @php($menus = \App\Facades\AdminBar::tree())
        @foreach (['start', 'end'] as $align)
            <div @class(['flex items-center gap-1', 'ms-auto' => $align === 'end'])>
                @foreach ($menus->where('align', $align) as $menu)
                    @if ($menu['children']->isEmpty())
                        <a
                            href="{{ $menu['url'] }}"
                            @if ($menu['label']) aria-label="{{ $menu['label'] }}" @endif
                            class="hover:bg-accent hover:text-accent-foreground [&_svg]:size-4 flex items-center gap-2 rounded-sm px-2 py-1 text-sm font-medium"
                        >
                            <x-admin-bar.menu-title :icon="$menu['icon']">{{ $menu['title'] }}</x-admin-bar.menu-title>
                        </a>
                    @else
                        {{-- With a mouse, the menu opens on hover, so a click follows the link of the trigger. --}}
                        <div
                            x-data="{
                                closeTimer: null,
                                hoverOpen(id, event) {
                                    if (event.pointerType !== 'mouse') return;
                                    clearTimeout(this.closeTimer);
                                    this.openMenu(id, false);
                                },
                                hoverClose(id, event) {
                                    if (event.pointerType !== 'mouse') return;
                                    this.closeTimer = setTimeout(
                                        () => this.active === id && this.closeMenu(false),
                                        200,
                                    );
                                },
                            }"
                            class="contents"
                        >
                            <x-ui.menubar-menu>
                                <x-admin-bar.menu-trigger
                                    class="[&_svg]:size-4 gap-2"
                                    :href="$menu['url']"
                                    :aria-label="$menu['label']"
                                    x-on:pointerenter="hoverOpen(id, $event)"
                                    x-on:pointerleave="hoverClose(id, $event)"
                                >
                                    <x-admin-bar.menu-title :icon="$menu['icon']">
                                        {{ $menu['title'] }}</x-admin-bar.menu-title>
                                </x-admin-bar.menu-trigger>
                                <x-ui.menubar-content
                                    data-admin-bar
                                    :align="$align"
                                    @class(['min-w-56' => $align === 'end'])
                                    x-on:pointerenter="hoverOpen(id, $event)"
                                    x-on:pointerleave="hoverClose(id, $event)"
                                >
                                    @foreach ($menu['children'] as $item)
                                        @if ($item['title'] instanceof \Illuminate\Contracts\Support\Htmlable)
                                            {{ $item['title'] }}
                                        @elseif ($item['children']->isNotEmpty())
                                            <x-ui.menubar-sub>
                                                <x-ui.menubar-sub-trigger>
                                                    @if ($item['icon'])
                                                        <x-dynamic-component :component="$item['icon']" />
                                                    @endif
                                                    {{ $item['title'] }}
                                                </x-ui.menubar-sub-trigger>
                                                <x-ui.menubar-sub-content data-admin-bar>
                                                    @foreach ($item['children'] as $child)
                                                        <x-ui.menubar-item :href="$child['url']">
                                                            @if ($child['icon'])
                                                                <x-dynamic-component :component="$child['icon']" />
                                                            @endif
                                                            {{ $child['title'] }}
                                                        </x-ui.menubar-item>
                                                    @endforeach
                                                </x-ui.menubar-sub-content>
                                            </x-ui.menubar-sub>
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
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </x-ui.menubar>
@endauth
