<?php

namespace Database\Factories;

use App\Entries\Layout;
use App\Enums\EntryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Layout>
 */
class LayoutFactory extends Factory
{
    protected $model = Layout::class;

    /**
     * @return array{type: string, status: string, slug: string, name: string, content: list<array{type: string, data?: array<string, mixed>}>, user_id: null, parent_id: null, order: int, owner: string}
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'type' => Layout::$type,
            'status' => EntryStatus::Internal->value,
            'slug' => Str::slug($name),
            'name' => $name,
            'content' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Above the content']],
                ['type' => 'content'],
            ],
            'user_id' => null,
            'parent_id' => null,
            'order' => 0,
            'owner' => 'core',
        ];
    }

    /**
     * The default layout, showing the entries that choose no other.
     */
    public function default(): static
    {
        return $this->state(fn (): array => [
            'slug' => Layout::DEFAULT,
            'name' => 'Default',
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn (): array => [
            'status' => EntryStatus::Trash->value,
        ]);
    }
}
