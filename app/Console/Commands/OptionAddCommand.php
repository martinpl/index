<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('option:add {key?} {value?}')]
#[Description('Adds a new option')]
class OptionAddCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('key');
        if (! $key) {
            $key = $this->ask('Option key');
        }

        $value = $this->argument('value');
        if (! $value) {
            $value = $this->ask('Option value');
        }

        $validator = Validator::make(['key' => $key, 'value' => $value], [
            'key' => ['required'],
            'value' => ['required'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (Option::where('key', $key)->exists()) {
            $this->error("Option [$key] already exists.");

            return self::FAILURE;
        }

        Option::create(['key' => $key, 'value' => $value]);
        $this->info("Added option [$key].");

        return self::SUCCESS;
    }
}
