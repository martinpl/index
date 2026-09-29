<?php

namespace App\Models\Concerns;

use App\Models\Site;

trait BelongsToSite
{
    public function initializeBelongsToSite(): void
    {
        $this->setTable($this->siteTable($this->getTable()));
    }

    protected function siteTable(string $table): string
    {
        return Site::current()?->tablePrefix().$table;
    }
}
