<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * @return array{domain: string, path: string}
     */
    public function definition(): array
    {
        return [
            'domain' => fake()->unique()->domainName(),
            'path' => '/',
        ];
    }
}
