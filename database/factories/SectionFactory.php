<?php

namespace Database\Factories;

use App\Entries\Section;
use App\Enums\EntryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /**
     * @return array{type: string, status: string, slug: string, name: string, content: list<array{type: string, data: array<string, mixed>}>, user_id: null, parent_id: null, order: int, owner: string}
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'type' => Section::$type,
            'status' => EntryStatus::Internal->value,
            'slug' => Str::slug($name),
            'name' => $name,
            'content' => [['type' => 'paragraph', 'data' => ['text' => fake()->sentence()]]],
            'user_id' => null,
            'parent_id' => null,
            'order' => 0,
            'owner' => 'core',
        ];
    }

    public function trashed(): static
    {
        return $this->state(fn (): array => [
            'status' => EntryStatus::Trash->value,
        ]);
    }
}
