<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @action('head')
</head>
<body @filter('body.attributes', new \Illuminate\View\ComponentAttributeBag(['class' => 'blocks']))>
    @action('body.open')
    {{ $slot }}
    @action('footer')
</body>
</html>
