<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use App\Facades\Theme;
use App\Models\Option;
use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('index:install
    {--name= : The name of the site}
    {--domain= : The domain of the site}')]
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

        $domain = $this->option('domain');
        if (! $domain) {
            $domain = $this->ask('Site domain', 'localhost');
        }

        $validator = Validator::make(['name' => $name, 'domain' => $domain], [
            'name' => ['required'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Site::create([
            'domain' => $domain,
        ]);
        Option::set('site_name', $name, 'core');
        Theme::activate('theme');
        Plugin::activate('blog');
        $this->info('Site installed successfully.');

        return self::SUCCESS;
    }
}
