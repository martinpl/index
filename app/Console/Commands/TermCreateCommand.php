<?php

namespace App\Console\Commands;

use App\Facades\TermType;
use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[Signature('term:create
    {type : The key of the term type}
    {name : The name of the term}
    {--slug= : The slug of the term}
    {--description= : The description of the term}
    {--parent= : The ID of the parent term}')]
#[Description('Creates a term')]
class TermCreateCommand extends Command
{
    public function handle(): int
    {
        $type = $this->argument('type');
        if (! TermType::has($type)) {
            $this->error("Term type [$type] does not exist.");

            return self::FAILURE;
        }

        $attributes = [
            'type' => $type,
            'slug' => $this->option('slug') ?? Str::slug($this->argument('name')),
            'name' => $this->argument('name'),
            'description' => $this->option('description'),
            'parent_id' => $this->option('parent'),
        ];

        $validator = Validator::make($attributes, [
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique(Term::class)->where('type', $type)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', Rule::exists(Term::class, 'id')->where('type', $type)],
        ], attributes: ['parent_id' => 'parent']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $term = Term::create($attributes);
        $this->info("Created term [$term->id].");

        return self::SUCCESS;
    }
}
