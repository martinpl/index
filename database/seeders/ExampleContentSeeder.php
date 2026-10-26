<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExampleContentSeeder extends Seeder
{
    /**
     * Seed example pages, posts, categories, tags and authors.
     */
    public function run(): void
    {
        $admin = User::where('email', 'email@example.com')->first() ?? User::firstOrFail();

        $this->createPages($admin);

        $categories = collect(['News', 'Tutorials', 'Releases'])
            ->map(fn (string $name): Term => Term::factory()->create(['type' => 'category', 'slug' => null, 'name' => $name]));

        $tags = collect(['Laravel', 'PHP', 'CMS', 'Livewire', 'Tailwind'])
            ->map(fn (string $name): Term => Term::factory()->create(['type' => 'tag', 'slug' => null, 'name' => $name]));

        $authors = User::factory(2)->create()->push($admin);

        Entry::factory(12)
            ->published()
            ->sequence(fn () => ['user_id' => $authors->random()->id])
            ->create(['type' => 'post'])
            ->each(fn (Entry $post) => $post->terms()->attach([
                $categories->random()->id,
                ...$tags->random(2)->pluck('id'),
            ]));

        Entry::factory(2)->create(['type' => 'post', 'user_id' => $admin->id]);
    }

    /**
     * Create example pages and assign the blog page to the posts page role.
     */
    protected function createPages(User $author): void
    {
        $pages = collect(['About', 'Contact', 'Blog'])
            ->mapWithKeys(fn (string $name): array => [$name => Entry::factory()->published()->create([
                'type' => 'page',
                'slug' => null,
                'name' => $name,
                'user_id' => $author->id,
            ])]);

        Setting::set('page_roles', [...Setting::get('page_roles', []), 'posts' => $pages['Blog']->id], 'core');
    }
}
