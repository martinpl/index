<?php

use App\Facades\Action;
use App\Facades\Filter;
use Illuminate\Support\Facades\Blade;

class ShoutFilter
{
    public function handle(string $value): string
    {
        return strtoupper($value);
    }
}

class SeoHooks
{
    #[App\Attributes\Action('seo.head')]
    public function meta(): void
    {
        echo '<meta name="generator" content="Index">';
    }

    #[App\Attributes\Filter('title')]
    #[App\Attributes\Filter('heading', priority: 20)]
    public function suffix(string $value): string
    {
        return "$value - Index";
    }
}

test('runs actions in priority order', function () {
    $calls = [];

    Action::add('init', function (string $site) use (&$calls) {
        $calls[] = "late $site";
    }, 20);
    Action::add('init', function (string $site) use (&$calls) {
        $calls[] = "early $site";
    }, 5);

    Action::run('init', 'main');

    expect($calls)->toBe(['early main', 'late main'])
        ->and(Action::fired('init'))->toBe(1)
        ->and(Action::fired('missing'))->toBe(0);
});

test('applies filters with extra arguments and class callbacks', function () {
    Filter::add('title', fn (string $title, string $separator) => "$title $separator Index");
    Filter::add('title', ShoutFilter::class, 20);

    expect(Filter::apply('title', 'Home', '|'))->toBe('HOME | INDEX')
        ->and(Filter::apply('missing', 'Home'))->toBe('Home');
});

test('keeps actions and filters apart', function () {
    Action::add('title', fn () => null);

    expect(Filter::apply('title', 'Home'))->toBe('Home')
        ->and(Filter::has('title'))->toBeFalse();
});

test('forgets callbacks', function () {
    $suffix = fn (string $value) => "$value!";

    Filter::add('title', $suffix);
    Filter::add('title', 'strtoupper');
    Filter::forget('title', $suffix);

    expect(Filter::apply('title', 'home'))->toBe('HOME');

    Filter::forget('title', 'strtoupper');

    expect(Filter::has('title'))->toBeFalse();
});

test('subscribes attributed methods and runs hooks from blade', function () {
    Action::subscribe(SeoHooks::class);
    Filter::subscribe(SeoHooks::class);

    expect(Filter::apply('heading', 'Home'))->toBe('Home - Index')
        ->and(Blade::render("@action('seo.head')<title>@filter('title', \$title)</title>", ['title' => 'Home']))
        ->toBe('<meta name="generator" content="Index"><title>Home - Index</title>');
});
