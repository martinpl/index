<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $entry->name }}</title>
    </head>
    <body>
        <h1>{{ $entry->name }}</h1>

        {{ $entry->content }}
    </body>
</html>
