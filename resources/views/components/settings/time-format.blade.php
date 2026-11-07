@props(['field', 'path', 'value' => null])

<x-settings.format :$field :$path :$value :formats="['H:i', 'g:i A']" />
