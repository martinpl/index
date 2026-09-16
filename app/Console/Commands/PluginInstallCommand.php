<?php

namespace App\Console\Commands;

use App\Actions\InstallPlugin;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:install {source : Path or HTTPS URL to a plugin ZIP archive}')]
#[Description('Installs a plugin from a ZIP archive')]
class PluginInstallCommand extends Command
{
    public function handle(InstallPlugin $installPlugin): int
    {
        try {
            $name = $installPlugin($this->argument('source'));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Installed plugin [$name].");

        return self::SUCCESS;
    }
}
