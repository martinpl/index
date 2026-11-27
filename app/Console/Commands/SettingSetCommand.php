<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('setting:set {key?} {value?} {--owner=core : The owner of the setting, e.g. core or plugins/seo} {--autoload : Load the setting with the others on every request instead of only when read}')]
#[Description('Sets a setting value')]
class SettingSetCommand extends Command
{
    public function handle(): int
    {
        $key = $this->argument('key');
        if (! $key) {
            $key = $this->ask('Setting key');
        }

        $value = $this->argument('value');
        if (! $value) {
            $value = $this->ask('Setting value');
        }

        $validator = Validator::make(['key' => $key, 'value' => $value, 'owner' => $this->option('owner')], [
            'key' => ['required'],
            'value' => ['required'],
            'owner' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Setting::set($key, $value, $this->option('owner'), $this->option('autoload') ?: null);
        $this->info("Set setting [$key].");

        return self::SUCCESS;
    }
}
