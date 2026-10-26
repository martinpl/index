<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->call('index:install', [
            '--name' => 'Index',
            '--domain' => 'localhost',
            '--admin-name' => 'Admin',
            '--admin-email' => 'email@example.com',
            '--admin-password' => 'password',
        ]);

        $this->call(ExampleContentSeeder::class);
    }
}
