<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('entry:update
    {entry : The ID of the entry}
    {--status= : The status of the entry, e.g. publish, draft or trash}
    {--slug= : The slug of the entry}
    {--name= : The name of the entry}
    {--content= : The content of the entry}
    {--user= : The ID of the user who authored the entry}
    {--parent= : The ID of the parent entry}
    {--order= : The order of the entry}')]
#[Description('Updates an entry')]
class EntryUpdateCommand extends Command
{
    public function handle(): int
    {
        $entryId = $this->argument('entry');
        $entry = Entry::withAnyStatus()->find($entryId);
        if (! $entry) {
            $this->error("Entry [$entryId] does not exist.");

            return self::FAILURE;
        }

        $attributes = array_filter([
            'status' => $this->option('status'),
            'slug' => $this->option('slug'),
            'name' => $this->option('name'),
            'content' => $this->option('content'),
            'user_id' => $this->option('user'),
            'parent_id' => $this->option('parent'),
            'order' => $this->option('order'),
        ], fn (?string $value): bool => $value !== null);

        if (empty($attributes)) {
            $this->error('No fields to update.');

            return self::FAILURE;
        }

        $validator = Validator::make($attributes, [
            'status' => ['sometimes', 'required', 'alpha_dash', 'max:255'],
            'slug' => ['sometimes', 'required', 'alpha_dash', 'lowercase', 'max:255', Rule::unique(Entry::class)->where('type', $entry->type)->ignore($entry)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'integer', Rule::exists(User::class, 'id')],
            'parent_id' => ['sometimes', 'integer', Rule::notIn([$entry->id]), Rule::exists(Entry::class, 'id')->where('type', $entry->type)],
            'order' => ['sometimes', 'integer'],
        ], attributes: ['user_id' => 'user', 'parent_id' => 'parent']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $entry->update($attributes);
        $this->info("Updated entry [$entryId].");

        return self::SUCCESS;
    }
}
