@php($tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $level : 'h2')

<{{ $tag }} {{ $attributes }}>{{ $text }}</{{ $tag }}>
