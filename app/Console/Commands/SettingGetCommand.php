<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('setting:get {key?}')]
#[Description('Gets a setting value')]
class SettingGetCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('key');
        if (! $key) {
            $key = $this->ask('Setting key');
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

        if (! Setting::exists($key)) {
            $this->error("Setting [$key] does not exist.");

            return self::FAILURE;
        }

        $this->line(Setting::get($key));

        return self::SUCCESS;
    }
}
