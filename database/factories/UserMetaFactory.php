<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMeta>
 */
class UserMetaFactory extends Factory
{
    /**
     * @return array{user_id: Factory<User>, key: string, value: string, owner: string}
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'owner' => 'core',
        ];
    }
}
