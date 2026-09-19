<?php

namespace App\Console\Commands;

use App\Actions\InstallTheme;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:install {source : Path or HTTPS URL to a theme ZIP archive}')]
#[Description('Installs a theme from a ZIP archive')]
class ThemeInstallCommand extends Command
{
    public function handle(InstallTheme $installTheme): int
    {
        try {
            $name = $installTheme($this->argument('source'));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Installed theme [$name].");

        return self::SUCCESS;
    }
}
