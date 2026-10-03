<?php

namespace Blog;

use App\Facades\EntryType;
use App\Facades\TermType;
use Blog\EntryTypes\Post;
use Blog\TermTypes\Category;
use Blog\TermTypes\Tag;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        EntryType::register(Post::class);
        TermType::register(Category::class);
        TermType::register(Tag::class);
    }
}
