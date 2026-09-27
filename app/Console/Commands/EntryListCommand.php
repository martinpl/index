<?php

namespace App\Console\Commands;

use App\Models\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('entry:list
    {--type= : Only list entries of the given entry type}
    {--status=publish : Only list entries with the given status}')]
#[Description('Lists entries')]
class EntryListCommand extends Command
{
    public function handle(): int
    {
        $entries = Entry::withAnyStatus()
            ->where('status', $this->option('status'))
            ->when($this->option('type'), fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            $this->info('No entries found.');

            return self::SUCCESS;
        }

        $this->table(['id', 'type', 'status', 'slug', 'name', 'date'], $entries
            ->map(fn (Entry $entry): array => [
                $entry->id,
                $entry->type,
                $entry->status,
                $entry->slug,
                $entry->name,
                $entry->date,
            ]));

        return self::SUCCESS;
    }
}
