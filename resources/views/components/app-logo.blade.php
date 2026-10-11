@props([
    'sidebar' => false,
])

<a {{ $attributes->class(['flex items-center gap-2 font-medium', 'rounded-md p-2 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' => $sidebar]) }}>
    <span class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
        <x-app-logo-icon class="size-5 fill-current" />
    </span>
    <span class="truncate text-sm font-semibold">{{ config('app.name', 'Laravel') }}</span>
</a>
