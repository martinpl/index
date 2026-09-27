<?php

namespace App\Console\Commands;

use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term:list {type : The key of the term type}')]
#[Description('Lists terms')]
class TermListCommand extends Command
{
    public function handle(): int
    {
        $terms = Term::where('type', $this->argument('type'))
            ->withCount('entries')
            ->orderBy('name')
            ->get();

        if ($terms->isEmpty()) {
            $this->info('No terms found.');

            return self::SUCCESS;
        }

        $this->table(['id', 'slug', 'name', 'parent', 'count'], $terms
            ->map(fn (Term $term): array => [
                $term->id,
                $term->slug,
                $term->name,
                $term->parent_id,
                $term->entries_count,
            ]));

        return self::SUCCESS;
    }
}
