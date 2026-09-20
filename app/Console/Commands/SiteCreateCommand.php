<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('site:create')]
#[Description('Creates a site')]
class SiteCreateCommand extends Command
{
    public function handle(): int
    {
        $site = Site::create();

        $this->info("Created site [$site->id].");

        return self::SUCCESS;
    }
}
