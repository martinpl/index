<?php

namespace App\Console\Commands;

use App\Facades\EntryType;
use App\Models\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[Signature('entry:create
    {type : The key of the entry type}
    {name : The name of the entry}
    {--status=draft : The status of the entry, e.g. publish, draft or trash}
    {--slug= : The slug of the entry}
    {--content= : The content of the entry}')]
#[Description('Creates an entry')]
class EntryCreateCommand extends Command
{
    public function handle(): int
    {
        $type = $this->argument('type');
        if (! EntryType::has($type)) {
            $this->error("Entry type [$type] does not exist.");

            return self::FAILURE;
        }

        $attributes = [
            'type' => $type,
            'status' => $this->option('status'),
            'slug' => $this->option('slug') ?? Str::slug($this->argument('name')),
            'name' => $this->argument('name'),
            'content' => $this->option('content'),
        ];

        $validator = Validator::make($attributes, [
            'status' => ['required', 'alpha_dash', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique(Entry::class)->where('type', $type)],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $entry = Entry::create($attributes);
        $this->info("Created entry [$entry->id].");

        return self::SUCCESS;
    }
}
