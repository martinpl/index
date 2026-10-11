@php($user = auth()->user())

<x-ui.dropdown-menu>
    <x-ui.dropdown-menu-trigger>
        <x-ui.sidebar-menu-button
            size="lg"
            data-test="sidebar-menu-button"
            ::data-state="open ? 'open' : 'closed'"
            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
        >
            <x-ui.avatar class="rounded-lg">
                <x-ui.avatar-fallback class="rounded-lg">{{ $user->initials() }}</x-ui.avatar-fallback>
            </x-ui.avatar>
            <div class="grid flex-1 text-start text-sm leading-tight">
                <span class="truncate font-medium">{{ $user->name }}</span>
                <span class="truncate text-xs">{{ $user->email }}</span>
            </div>
            <x-lucide-chevrons-up-down class="ms-auto size-4" />
        </x-ui.sidebar-menu-button>
    </x-ui.dropdown-menu-trigger>

    <x-ui.dropdown-menu-content class="min-w-56 rounded-lg" side="top" align="end">
        <x-ui.dropdown-menu-label class="p-0 font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <x-ui.avatar class="rounded-lg">
                    <x-ui.avatar-fallback class="rounded-lg">{{ $user->initials() }}</x-ui.avatar-fallback>
                </x-ui.avatar>
                <div class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-medium">{{ $user->name }}</span>
                    <span class="text-muted-foreground truncate text-xs">{{ $user->email }}</span>
                </div>
            </div>
        </x-ui.dropdown-menu-label>
        <x-ui.dropdown-menu-separator />
        <x-ui.dropdown-menu-item :href="route('profile.edit')" wire:navigate>
            <x-lucide-settings />
            {{ __('Settings') }}
        </x-ui.dropdown-menu-item>
        <x-ui.dropdown-menu-separator />
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.dropdown-menu-item type="submit" data-test="logout-button">
                <x-lucide-log-out />
                {{ __('Log out') }}
            </x-ui.dropdown-menu-item>
        </form>
    </x-ui.dropdown-menu-content>
</x-ui.dropdown-menu>
