<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen antialiased">
    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-2">
            <a href="{{ page_url('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <span class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                    <x-app-logo-icon class="text-foreground size-9 fill-current" />
                </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
            <div class="flex flex-col gap-6">{{ $slot }}</div>
        </div>
    </div>

    @persist('toast')
        <x-ui.sonner />
    @endpersist
</body>
</html>
