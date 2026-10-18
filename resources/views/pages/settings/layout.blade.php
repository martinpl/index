<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav aria-label="{{ __('Settings') }}" class="flex flex-col gap-1">
            @foreach ([
                'profile.edit' => __('Profile'),
                'security.edit' => __('Security'),
            ] as $routeName => $label)
                <x-ui.button
                    variant="ghost"
                    :href="route($routeName)"
                    wire:navigate
                    @class(['justify-start', 'bg-accent text-accent-foreground' => request()->routeIs($routeName)])
                >
                    {{ $label }}
                </x-ui.button>
            @endforeach
        </nav>
    </div>

    <x-ui.separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="font-medium">{{ $heading ?? '' }}</h2>
        <x-ui.typography variant="muted">{{ $subheading ?? '' }}</x-ui.typography>

        <div class="mt-5 w-full max-w-lg">{{ $slot }}</div>
    </div>
</div>
