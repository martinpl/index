<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ManagesMeta;
use App\Models\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry:meta
    {action : The action to perform (get, set, delete or list)}
    {entry : The ID of the entry}
    {key? : The meta key}
    {value? : The meta value}')]
#[Description('Manages the meta of an entry')]
class EntryMetaCommand extends Command
{
    use ManagesMeta;

    public function handle(): int
    {
        if (! $this->isSupportedMetaAction()) {
            return self::FAILURE;
        }

        $entryId = $this->argument('entry');
        $entry = Entry::withAnyStatus()->find($entryId);
        if (! $entry) {
            $this->error("Entry [$entryId] does not exist.");

            return self::FAILURE;
        }

        return $this->manageMeta($entry);
    }
}
