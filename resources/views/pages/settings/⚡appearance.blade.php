<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component
{
    //
}; ?>

<section class="w-full">
    <x-pages::settings.layout
        :heading="__('Appearance')"
        :subheading="__('Update the appearance settings for your account')"
    >
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
