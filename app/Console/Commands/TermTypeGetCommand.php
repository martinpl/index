<?php

namespace App\Console\Commands;

use App\Facades\TermType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term-type:get {type : The key of the term type}')]
#[Description('Gets a term type')]
class TermTypeGetCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('type');
        $termType = TermType::get($key);
        if (! $termType) {
            $this->error("Term type [$key] does not exist.");

            return self::FAILURE;
        }

        $this->table(['field', 'value'], [
            ['key', $termType['key']],
            ['name', $termType['name']],
            ['entry types', implode(', ', $termType['entry_types'])],
        ]);

        return self::SUCCESS;
    }
}
