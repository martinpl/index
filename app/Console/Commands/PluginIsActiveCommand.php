<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:is-active {plugin}')]
#[Description('Checks whether a plugin is active')]
class PluginIsActiveCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('plugin');
        if (! Plugin::isInstalled($name)) {
            $this->error("Plugin [$name] does not exist.");

            return self::FAILURE;
        }

        $this->line(Plugin::isActive($name) ? 'yes' : 'no');

        return self::SUCCESS;
    }
}
