<?php

namespace App\Console\Commands;

use App\Facades\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugin:list')]
#[Description('Lists installed plugins')]
class PluginListCommand extends Command
{
    public function handle(): int
    {
        $installedPlugins = Plugin::list();
        if ($installedPlugins->isEmpty()) {
            $this->info('No plugins found.');

            return self::SUCCESS;
        }

        $this->table(['name', 'status'], $installedPlugins
            ->map(fn (array $plugin): array => [$plugin['slug'], $plugin['status']]));

        return self::SUCCESS;
    }
}
