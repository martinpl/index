<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:is-active {theme}')]
#[Description('Checks whether a theme is active')]
class ThemeIsActiveCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('theme');
        if (! Theme::isInstalled($name)) {
            $this->error("Theme [$name] does not exist.");

            return self::FAILURE;
        }

        $this->line(Theme::isActive($name) ? 'yes' : 'no');

        return self::SUCCESS;
    }
}
