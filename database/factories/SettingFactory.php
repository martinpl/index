<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array{key: string, value: string, owner: string}
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'owner' => 'core',
        ];
    }
}
