<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('site:create
    {domain : The domain of the site}
    {--path=/ : The path of the site}')]
#[Description('Creates a site')]
class SiteCreateCommand extends Command
{
    public function handle(): int
    {
        $attributes = [
            'domain' => $this->argument('domain'),
            'path' => $this->option('path'),
        ];

        $validator = Validator::make($attributes, [
            'domain' => ['required', 'string', 'max:255', Rule::unique(Site::class)->where('path', Site::make($attributes)->path)],
            'path' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $site = Site::create($attributes);

        $this->info("Created site [$site->id].");

        return self::SUCCESS;
    }
}
