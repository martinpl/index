<?php

namespace Database\Factories;

use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    /**
     * @return array{key: string, value: string}
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
        ];
    }
}
