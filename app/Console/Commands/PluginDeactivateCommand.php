<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:deactivate {plugin}')]
#[Description('Deactivates an installed plugin')]
class PluginDeactivateCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('plugin');
        if (! Plugin::isInstalled($name)) {
            $this->error("Plugin [$name] does not exist.");

            return self::FAILURE;
        }

        Plugin::deactivate($name);
        $this->info("Deactivated plugin [$name].");

        return self::SUCCESS;
    }
}
