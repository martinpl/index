<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:deactivate {theme}')]
#[Description('Deactivates an installed theme')]
class ThemeDeactivateCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('theme');
        if (! Theme::isInstalled($name)) {
            $this->error("Theme [$name] does not exist.");

            return self::FAILURE;
        }

        Theme::deactivate($name);
        $this->info("Deactivated theme [$name].");

        return self::SUCCESS;
    }
}
