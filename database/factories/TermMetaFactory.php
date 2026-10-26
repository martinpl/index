<?php

namespace Database\Factories;

use App\Models\Term;
use App\Models\TermMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermMeta>
 */
class TermMetaFactory extends Factory
{
    /**
     * @return array{term_id: Factory<Term>, key: string, value: string, owner: string}
     */
    public function definition(): array
    {
        return [
            'term_id' => Term::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'owner' => 'core',
        ];
    }
}
