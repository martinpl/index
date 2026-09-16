<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:activate {plugin}')]
#[Description('Activates an installed plugin')]
class PluginActivateCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('plugin');
        if (! Plugin::isInstalled($name)) {
            $this->error("Plugin [$name] does not exist.");

            return self::FAILURE;
        }

        Plugin::activate($name);
        $this->info("Activated plugin [$name].");

        return self::SUCCESS;
    }
}
