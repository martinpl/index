<?php

namespace Database\Factories;

use App\EntryTypes\Media;
use App\Enums\EntryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Media made by the factory points at files that do not exist, use the `StoreMedia` action for media with real files.
 *
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array{type: string, status: string, slug: string, name: string, content: string, user_id: null, parent_id: null, order: int, owner: string}
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();
        $slug = Str::slug($name);

        return [
            'type' => Media::$type,
            'status' => EntryStatus::Internal->value,
            'slug' => $slug,
            'name' => $name,
            'content' => 'media/'.now()->format('Y/m')."/$slug.jpg",
            'user_id' => null,
            'parent_id' => null,
            'order' => 0,
            'owner' => 'core',
        ];
    }

    /**
     * Give the media file the meta of a JPEG image, matching the path it is defined with.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Media $media): void {
            $media->setMeta('mime_type', 'image/jpeg', 'core');
            $media->setMeta('metadata', ['filesize' => fake()->numberBetween(10_000, 2_000_000), 'width' => 1200, 'height' => 800], 'core');
        });
    }

    /**
     * Make the media file a PDF document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes): array => [
            'content' => 'media/'.now()->format('Y/m')."/{$attributes['slug']}.pdf",
        ])->afterCreating(function (Media $media): void {
            $media->setMeta('mime_type', 'application/pdf', 'core');
            $media->setMeta('metadata', ['filesize' => fake()->numberBetween(10_000, 2_000_000)], 'core');
        });
    }

    /**
     * Give the media file the given meta, replacing the meta of its kind of file.
     *
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): static
    {
        return $this->afterCreating(function (Media $media) use ($meta): void {
            foreach ($meta as $key => $value) {
                $media->setMeta($key, $value, 'core');
            }
        });
    }
}
