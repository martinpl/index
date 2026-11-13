{{--
    Uploads the files dropped on it or chosen with it to the Livewire `model` property,
    which takes a list of files when `multiple`, or a single file otherwise.
--}}
@props(['model', 'multiple' => true])

@php
    $messages = collect($errors->get($model))->merge($errors->get($model.'.*'))->flatten()->unique()->all();
@endphp

<div {{ $attributes->twMerge('flex flex-col gap-2') }}>
    <x-ui.file-upload
        wire:model="{{ $model }}"
        :$multiple
        :accept="collect(\App\EntryTypes\Media::extensions())->map(fn (string $extension): string => '.'.$extension)->join(',')"
    />
    <x-ui.field-error :$messages />
</div>
