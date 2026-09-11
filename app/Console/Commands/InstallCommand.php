<?php

namespace App\Console\Commands;

use App\Models\Option;
use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('index:install {--name= : The name of the site}')]
#[Description('Runs installation process')]
class InstallCommand extends Command
{
    public function handle(): int
    {
        $exitCode = $this->call('migrate');
        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        if (Site::exists()) {
            $this->info('Site already installed.');

            return self::SUCCESS;
        }

        $name = $this->option('name');
        if (! $name) {
            $name = $this->ask('Site name');
        }

        $validator = Validator::make(['name' => $name], [
            'name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Site::create();
        Option::create(['key' => 'site_name', 'value' => $name]);
        $this->info('Site installed successfully.');

        return self::SUCCESS;
    }
}
