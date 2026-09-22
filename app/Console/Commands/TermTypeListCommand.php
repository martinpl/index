<?php

namespace App\Console\Commands;

use App\Facades\TermType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term-type:list')]
#[Description('Lists registered term types')]
class TermTypeListCommand extends Command
{
    public function handle(): int
    {
        $termTypes = TermType::list();
        if ($termTypes->isEmpty()) {
            $this->info('No term types found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'name', 'entry types'], $termTypes
            ->map(fn (array $termType): array => [
                $termType['key'],
                $termType['name'],
                implode(', ', $termType['entry_types']),
            ]));

        return self::SUCCESS;
    }
}
