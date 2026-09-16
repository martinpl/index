<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:delete {plugin}')]
#[Description('Deletes an inactive plugin')]
class PluginDeleteCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('plugin');
        if (! Plugin::isInstalled($name)) {
            $this->error("Plugin [$name] does not exist.");

            return self::FAILURE;
        }

        Plugin::delete($name);
        $this->info("Deleted plugin [$name].");

        return self::SUCCESS;
    }
}
