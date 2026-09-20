<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('site:list')]
#[Description('Lists sites')]
class SiteListCommand extends Command
{
    public function handle(): int
    {
        $sites = Site::orderBy('id')->get();
        if ($sites->isEmpty()) {
            $this->info('No sites found.');

            return self::SUCCESS;
        }

        $this->table(['id'], $sites->map(fn (Site $site): array => [$site->id]));

        return self::SUCCESS;
    }
}
