<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('site:delete {site : The ID of the site}')]
#[Description('Deletes a site')]
class SiteDeleteCommand extends Command
{
    public function handle(): int
    {
        $siteId = $this->argument('site');
        $site = Site::find($siteId);
        if (! $site) {
            $this->error("Site [$siteId] does not exist.");

            return self::FAILURE;
        }

        $site->delete();
        $this->info("Deleted site [$siteId].");

        return self::SUCCESS;
    }
}
