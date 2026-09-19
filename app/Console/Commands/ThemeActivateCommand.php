<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:activate {theme}')]
#[Description('Activates an installed theme')]
class ThemeActivateCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('theme');
        if (! Theme::isInstalled($name)) {
            $this->error("Theme [$name] does not exist.");

            return self::FAILURE;
        }

        Theme::activate($name);
        $this->info("Activated theme [$name].");

        return self::SUCCESS;
    }
}
