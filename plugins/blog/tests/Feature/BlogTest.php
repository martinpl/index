<?php

use App\EntryTypes\Page;
use App\Facades\EntryType;
use App\Facades\Plugin;
use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Option;
use App\Models\Term;
use Blog\EntryTypes\Post;
use Blog\TermTypes\Category;
use Blog\TermTypes\Tag;

beforeEach(function (): void {
    Option::set('active_plugins', ['blog'], 'core');
    Plugin::boot();
});

test('registers posts with categories and tags', function () {
    expect(EntryType::get('post'))->toBe([
        'key' => 'post',
        'name' => 'Post',
        'slug' => 'posts',
        'model' => Post::class,
    ])
        ->and(TermType::get('category'))->toBe([
            'key' => 'category',
            'name' => 'Category',
            'entry_types' => ['post'],
            'model' => Category::class,
        ])
        ->and(TermType::get('tag'))->toBe([
            'key' => 'tag',
            'name' => 'Tag',
            'entry_types' => ['post'],
            'model' => Tag::class,
        ]);
});

test('registers the posts page role', function () {
    expect(Page::roles())->toBe([
        'home' => 'Home page',
        'posts' => 'Posts page',
    ]);
});

test('hydrates posts, categories and tags as their classes', function () {
    $post = Entry::factory()->published()->create(['type' => 'post']);
    $category = Term::factory()->create(['type' => 'category']);
    $tag = Term::factory()->create(['type' => 'tag']);

    expect(Entry::find($post->id))->toBeInstanceOf(Post::class)
        ->and(Term::find($category->id))->toBeInstanceOf(Category::class)
        ->and(Term::find($tag->id))->toBeInstanceOf(Tag::class);
});
