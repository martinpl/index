<?php

namespace App\Console\Commands;

use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term:delete
    {type : The key of the term type}
    {term : The ID of the term}')]
#[Description('Deletes a term')]
class TermDeleteCommand extends Command
{
    public function handle(): int
    {
        $termId = $this->argument('term');
        $term = Term::where('type', $this->argument('type'))->find($termId);
        if (! $term) {
            $this->error("Term [$termId] does not exist.");

            return self::FAILURE;
        }

        $term->delete();
        $this->info("Deleted term [$termId].");

        return self::SUCCESS;
    }
}
