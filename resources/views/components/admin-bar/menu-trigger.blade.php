@props(['href' => null])

{{--
    The trigger of an admin bar menu: with an `href`, a link wired like `x-ui.menubar-trigger`, followed once its menu
    is open, so a first tap opens the menu on touch screens, while with a mouse the menu opens on hover before the click.
--}}
@if (filled($href))
    <a
        href="{{ $href }}"
        x-ref="trigger"
        role="menuitem"
        aria-haspopup="menu"
        x-init="register(id, $el)"
        :id="id + '-trigger'"
        :aria-controls="id"
        :aria-expanded="active === id"
        :tabindex="rovingId === id ? 0 : -1"
        @click="
            if (active !== id) {
                $event.preventDefault();
                openMenu(id, false);
            }
        "
        @focus="rovingId = id"
        @mouseenter="
            if (active !== null) {
                active = id;
                rovingId = id;
            }
        "
        @keydown.down.prevent.stop="openMenu(id)"
        @keydown.enter.prevent.stop="openMenu(id)"
        @keydown.space.prevent.stop="openMenu(id)"
        @keydown.right.prevent.stop="moveTrigger(1, id)"
        @keydown.left.prevent.stop="moveTrigger(-1, id)"
        :data-state="active === id ? 'open' : 'closed'"
        data-slot="menubar-trigger"
        {{ $attributes->twMerge('focus:bg-accent focus:text-accent-foreground data-[state=open]:bg-accent data-[state=open]:text-accent-foreground flex items-center rounded-sm px-2 py-1 text-sm font-medium outline-hidden select-none') }}
    >
        {{ $slot }}
    </a>
@else
    <x-ui.menubar-trigger {{ $attributes }}>{{ $slot }}</x-ui.menubar-trigger>
@endif
