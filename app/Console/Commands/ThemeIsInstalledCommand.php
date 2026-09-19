<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:is-installed {theme}')]
#[Description('Checks whether a theme is installed')]
class ThemeIsInstalledCommand extends Command
{
    public function handle(): int
    {
        $this->line(Theme::isInstalled($this->argument('theme')) ? 'yes' : 'no');

        return self::SUCCESS;
    }
}
