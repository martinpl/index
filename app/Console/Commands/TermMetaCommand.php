<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ManagesMeta;
use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term:meta
    {action : The action to perform (get, set, delete or list)}
    {type : The key of the term type}
    {term : The ID of the term}
    {key? : The meta key}
    {value? : The meta value}
    {--owner=core : The owner of the meta, e.g. core or plugins/seo}')]
#[Description('Manages the meta of a term')]
class TermMetaCommand extends Command
{
    use ManagesMeta;

    public function handle(): int
    {
        if (! $this->isSupportedMetaAction()) {
            return self::FAILURE;
        }

        $termId = $this->argument('term');
        $term = Term::where('type', $this->argument('type'))->find($termId);
        if (! $term) {
            $this->error("Term [$termId] does not exist.");

            return self::FAILURE;
        }

        return $this->manageMeta($term);
    }
}
