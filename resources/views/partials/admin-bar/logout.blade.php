<x-ui.menubar-separator />
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <x-ui.menubar-item x-on:click="$el.form.submit()">
        <x-lucide-log-out />
        {{ __('Log out') }}
    </x-ui.menubar-item>
</form>
