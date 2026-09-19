<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:delete {theme}')]
#[Description('Deletes a theme')]
class ThemeDeleteCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('theme');
        if (! Theme::isInstalled($name)) {
            $this->error("Theme [$name] does not exist.");

            return self::FAILURE;
        }

        Theme::delete($name);
        $this->info("Deleted theme [$name].");

        return self::SUCCESS;
    }
}
