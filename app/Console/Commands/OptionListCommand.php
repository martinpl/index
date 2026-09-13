<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('option:list')]
#[Description('Lists all options')]
class OptionListCommand extends Command
{
    public function handle(): int
    {
        $options = Option::query()->orderBy('key')->get(['key', 'value']);
        if ($options->isEmpty()) {
            $this->info('No options found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'value'], $options->toArray());

        return self::SUCCESS;
    }
}
