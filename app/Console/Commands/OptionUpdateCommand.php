<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('option:update {key?} {value?}')]
#[Description('Updates an existing option')]
class OptionUpdateCommand extends Command
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

        $option = Option::where('key', $key)->first();
        if (! $option) {
            $this->error("Option [$key] does not exist.");

            return self::FAILURE;
        }

        $option->update(['value' => $value]);
        $this->info("Updated option [$key].");

        return self::SUCCESS;
    }
}
