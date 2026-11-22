<?php

use Illuminate\Support\Facades\Blade;

function renderLink(string $href): string
{
    return trim(Blade::render('<x-link :$href class="nav">Go</x-link>', ['href' => $href]));
}

test('links lead to paths under the address of the site, keeping anchors, queries and other addresses as they are', function () {
    expect(renderLink('/about'))->toBe('<a href="'.url('/about').'" class="nav">Go</a>')
        ->and(renderLink('#team'))->toBe('<a href="#team" class="nav">Go</a>')
        ->and(renderLink('?page=2'))->toBe('<a href="?page=2" class="nav">Go</a>')
        ->and(renderLink('https://example.com'))->toBe('<a href="https://example.com" class="nav">Go</a>')
        ->and(renderLink('//cdn.example.com/file'))->toBe('<a href="//cdn.example.com/file" class="nav">Go</a>');
});

test('links of a links field show their label, escaping it', function () {
    $html = trim(Blade::render('<x-link :$link />', ['link' => ['label' => 'About <us>', 'url' => '/about']]));

    expect($html)->toBe('<a href="'.url('/about').'">About &lt;us&gt;</a>');
});

test('links mark the one leading to the current page', function () {
    expect(renderLink('/'))->toBe('<a href="'.url('/').'" aria-current="page" class="nav">Go</a>');
});

test('links to URLs the site may not link to lead nowhere', function () {
    expect(renderLink('javascript:alert(1)'))->toBe('<a class="nav">Go</a>');
});
