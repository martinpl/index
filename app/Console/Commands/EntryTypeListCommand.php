<?php

namespace App\Console\Commands;

use App\Facades\EntryType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry-type:list')]
#[Description('Lists registered entry types')]
class EntryTypeListCommand extends Command
{
    public function handle(): int
    {
        $entryTypes = EntryType::list();
        if ($entryTypes->isEmpty()) {
            $this->info('No entry types found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'name'], $entryTypes
            ->map(fn (array $entryType): array => [$entryType['key'], $entryType['name']]));

        return self::SUCCESS;
    }
}
