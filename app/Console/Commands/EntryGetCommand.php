<?php

namespace App\Console\Commands;

use App\Models\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry:get {entry : The ID of the entry}')]
#[Description('Gets an entry')]
class EntryGetCommand extends Command
{
    public function handle(): int
    {
        $entryId = $this->argument('entry');
        $entry = Entry::withAnyStatus()->find($entryId);
        if (! $entry) {
            $this->error("Entry [$entryId] does not exist.");

            return self::FAILURE;
        }

        $this->table(['field', 'value'], [
            ['id', $entry->id],
            ['type', $entry->type],
            ['status', $entry->status],
            ['slug', $entry->slug],
            ['name', $entry->name],
            ['content', $entry->content],
            ['date', $entry->date],
            ['modified', $entry->modified],
        ]);

        return self::SUCCESS;
    }
}
