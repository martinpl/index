<?php

namespace App\Console\Commands;

use App\Models\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry:delete
    {entry : The ID of the entry}
    {--force : Skip the trash and permanently delete the entry}')]
#[Description('Deletes an entry')]
class EntryDeleteCommand extends Command
{
    public function handle(): int
    {
        $entryId = $this->argument('entry');
        $entry = Entry::withAnyStatus()->find($entryId);
        if (! $entry) {
            $this->error("Entry [$entryId] does not exist.");

            return self::FAILURE;
        }

        if ($this->option('force') || $entry->trashed()) {
            $entry->forceDelete();
            $this->info("Deleted entry [$entryId].");

            return self::SUCCESS;
        }

        $entry->delete();
        $this->info("Trashed entry [$entryId].");

        return self::SUCCESS;
    }
}
