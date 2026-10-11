@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <x-ui.typography variant="h3" as="h1">{{ $title }}</x-ui.typography>
    <x-ui.typography variant="muted">{{ $description }}</x-ui.typography>
</div>
