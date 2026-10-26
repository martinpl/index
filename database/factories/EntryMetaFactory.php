<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryMeta>
 */
class EntryMetaFactory extends Factory
{
    /**
     * @return array{entry_id: Factory<Entry>, key: string, value: string, owner: string}
     */
    public function definition(): array
    {
        return [
            'entry_id' => Entry::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'owner' => 'core',
        ];
    }
}
