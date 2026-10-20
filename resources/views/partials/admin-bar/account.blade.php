{{ $user->name }}
<x-ui.avatar class="size-6 rounded-sm">
    <x-ui.avatar-fallback class="rounded-sm text-xs">{{ $user->initials() }}</x-ui.avatar-fallback>
</x-ui.avatar>
