@props(['field', 'path', 'value' => null])

<x-settings.format :$field :$path :$value :formats="['Y-m-d', 'm/d/Y', 'd/m/Y', 'd.m.Y']" />
