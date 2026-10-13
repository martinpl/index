<?php

namespace App\Console\Commands;

use App\Actions\ScopePackage;
use App\Facades\Plugin;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:scope {plugin}')]
#[Description('Prefixes the plugin dependencies with its namespace')]
class PluginScopeCommand extends Command
{
    public function handle(ScopePackage $scopePackage): int
    {
        $name = $this->argument('plugin');
        if (! Plugin::isInstalled($name)) {
            $this->error("Plugin [$name] does not exist.");

            return self::FAILURE;
        }

        try {
            $scopePackage(Plugin::path($name));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Scoped plugin [$name].");

        return self::SUCCESS;
    }
}
