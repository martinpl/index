<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('option:delete {key?}')]
#[Description('Deletes an option')]
class OptionDeleteCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('key');
        if (! $key) {
            $key = $this->ask('Option key');
        }

        $validator = Validator::make(['key' => $key], [
            'key' => ['required'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! Option::exists($key)) {
            $this->error("Option [$key] does not exist.");

            return self::FAILURE;
        }

        Option::forget($key);
        $this->info("Deleted option [$key].");

        return self::SUCCESS;
    }
}
