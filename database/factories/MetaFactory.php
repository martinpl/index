<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\Meta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meta>
 */
class MetaFactory extends Factory
{
    /**
     * @return array{metable_type: string, metable_id: Factory<Entry>, key: string, value: string}
     */
    public function definition(): array
    {
        return [
            'metable_type' => (new Entry)->getMorphClass(),
            'metable_id' => Entry::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
        ];
    }
}
