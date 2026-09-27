<?php

namespace App\Console\Commands;

use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term:get
    {type : The key of the term type}
    {term : The ID of the term}')]
#[Description('Gets a term')]
class TermGetCommand extends Command
{
    public function handle(): int
    {
        $termId = $this->argument('term');
        $term = Term::where('type', $this->argument('type'))->find($termId);
        if (! $term) {
            $this->error("Term [$termId] does not exist.");

            return self::FAILURE;
        }

        $this->table(['field', 'value'], [
            ['id', $term->id],
            ['type', $term->type],
            ['slug', $term->slug],
            ['name', $term->name],
            ['description', $term->description],
            ['parent', $term->parent_id],
        ]);

        return self::SUCCESS;
    }
}
