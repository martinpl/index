<?php

namespace Database\Factories;

use App\Enums\EntryStatus;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * @return array{type: string, status: string, slug: string, name: string, content: string, user_id: null, parent_id: null, order: int}
     */
    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return [
            'type' => 'post',
            'status' => EntryStatus::Draft->value,
            'slug' => Str::slug($name),
            'name' => $name,
            'content' => fake()->paragraph(),
            'user_id' => null,
            'parent_id' => null,
            'order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => EntryStatus::Publish->value,
        ]);
    }
}
