<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('setting:list')]
#[Description('Lists all settings')]
class SettingListCommand extends Command
{
    public function handle(): int
    {
        $settings = Setting::query()->orderBy('key')->get(['key', 'value']);
        if ($settings->isEmpty()) {
            $this->info('No settings found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'value'], $settings->map(fn (Setting $setting): array => [
            $setting->key,
            is_string($setting->value) ? $setting->value : json_encode($setting->value),
        ]));

        return self::SUCCESS;
    }
}
