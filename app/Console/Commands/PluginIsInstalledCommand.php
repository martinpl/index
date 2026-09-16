<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:is-installed {plugin}')]
#[Description('Checks whether a plugin is installed')]
class PluginIsInstalledCommand extends Command
{
    public function handle(): int
    {
        $this->line(Plugin::isInstalled($this->argument('plugin')) ? 'yes' : 'no');

        return self::SUCCESS;
    }
}
