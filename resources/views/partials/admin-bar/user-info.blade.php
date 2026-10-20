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
