<?php

namespace App\Console\Commands;

use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('term:update
    {type : The key of the term type}
    {term : The ID of the term}
    {--slug= : The slug of the term}
    {--name= : The name of the term}
    {--description= : The description of the term}
    {--parent= : The ID of the parent term}')]
#[Description('Updates a term')]
class TermUpdateCommand extends Command
{
    public function handle(): int
    {
        $termId = $this->argument('term');
        $term = Term::where('type', $this->argument('type'))->find($termId);
        if (! $term) {
            $this->error("Term [$termId] does not exist.");

            return self::FAILURE;
        }

        $attributes = array_filter([
            'slug' => $this->option('slug'),
            'name' => $this->option('name'),
            'description' => $this->option('description'),
            'parent_id' => $this->option('parent'),
        ], fn (?string $value): bool => $value !== null);

        if (empty($attributes)) {
            $this->error('No fields to update.');

            return self::FAILURE;
        }

        $validator = Validator::make($attributes, [
            'slug' => ['sometimes', 'required', 'alpha_dash', 'max:255', Rule::unique(Term::class)->where('type', $term->type)->ignore($term)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'parent_id' => ['sometimes', 'integer', Rule::notIn([$term->id]), Rule::exists(Term::class, 'id')->where('type', $term->type)],
        ], attributes: ['parent_id' => 'parent']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $term->update($attributes);
        $this->info("Updated term [$termId].");

        return self::SUCCESS;
    }
}
