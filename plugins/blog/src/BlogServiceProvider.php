<?php

namespace Blog;

use App\Support\PackageServiceProvider;
use Blog\EntryTypes\Post;
use Blog\TermTypes\Category;
use Blog\TermTypes\Tag;

class BlogServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->addEntryTypes([Post::class]);
        $this->addTermTypes([Category::class, Tag::class]);
    }
}
