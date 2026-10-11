<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <x-ui.segmented-control
            name="appearance"
            :options="[
                ['value' => 'light', 'label' => __('Light'), 'icon' => 'sun'],
                ['value' => 'dark', 'label' => __('Dark'), 'icon' => 'moon'],
                ['value' => 'system', 'label' => __('System'), 'icon' => 'monitor'],
            ]"
            x-data
            x-init="$el.querySelector(`input[value='${$store.theme.mode}']`).checked = true"
            x-on:change="$store.theme.setMode($event.target.value)"
        />
    </x-pages::settings.layout>
</section>
