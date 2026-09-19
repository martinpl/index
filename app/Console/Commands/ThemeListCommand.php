<?php

namespace App\Console\Commands;

use App\Facades\Theme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:list')]
#[Description('Lists installed themes')]
class ThemeListCommand extends Command
{
    public function handle(): int
    {
        $installedThemes = Theme::list();
        if ($installedThemes->isEmpty()) {
            $this->info('No themes found.');

            return self::SUCCESS;
        }

        $this->table(['name', 'status'], $installedThemes
            ->map(fn (array $theme): array => [$theme['slug'], $theme['status']]));

        return self::SUCCESS;
    }
}
