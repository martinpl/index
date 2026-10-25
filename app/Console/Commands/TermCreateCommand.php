<?php

namespace App\Console\Commands;

use App\Facades\TermType;
use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('term:create
    {type : The key of the term type}
    {name : The name of the term}
    {--slug= : The slug of the term, generated from the name when omitted}
    {--description= : The description of the term}
    {--parent= : The ID of the parent term}
    {--owner=core : The owner of the term, e.g. core or plugins/seo}')]
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
            'slug' => $this->option('slug'),
            'name' => $this->argument('name'),
            'description' => $this->option('description'),
            'parent_id' => $this->option('parent'),
            'owner' => $this->option('owner'),
        ];

        $validator = Validator::make($attributes, [
            'slug' => ['nullable', 'alpha_dash', 'lowercase', 'max:255', Rule::unique(Term::class)->where('type', $type)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => TermType::get($type)['hierarchical'] ? ['nullable', 'integer', Rule::exists(Term::class, 'id')->where('type', $type)] : ['prohibited'],
            'owner' => ['required', 'string', 'max:255'],
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
