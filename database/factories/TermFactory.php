<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * @return array{type: string, slug: string, name: string, owner: string}
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'type' => 'category',
            'slug' => Str::slug($name),
            'name' => Str::headline($name),
            'owner' => 'core',
        ];
    }
}
