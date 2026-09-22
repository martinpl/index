<?php

namespace App\Console\Commands;

use App\Facades\EntryType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry-type:get {type : The key of the entry type}')]
#[Description('Gets an entry type')]
class EntryTypeGetCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('type');
        $entryType = EntryType::get($key);
        if (! $entryType) {
            $this->error("Entry type [$key] does not exist.");

            return self::FAILURE;
        }

        $this->table(['field', 'value'], [
            ['key', $entryType['key']],
            ['name', $entryType['name']],
        ]);

        return self::SUCCESS;
    }
}
