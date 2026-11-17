<?php

namespace Blog;

use App\Support\PackageServiceProvider;
use Blog\Entries\Post;
use Blog\Terms\Category;
use Blog\Terms\Tag;

class BlogServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->addEntryTypes([Post::class]);
        $this->addTermTypes([Category::class, Tag::class]);
    }
}
