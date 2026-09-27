<?php

namespace App\Console\Commands;

use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('entry:term
    {action : The action to perform (add, remove, set or list)}
    {entry : The ID of the entry}
    {type : The key of the term type}
    {terms?* : The slugs of the terms}')]
#[Description('Manages the terms of an entry')]
class EntryTermCommand extends Command
{
    public function handle(): int
    {
        $action = $this->argument('action');
        if (! in_array($action, ['add', 'remove', 'set', 'list'], true)) {
            $this->error("Action [$action] is not supported.");

            return self::FAILURE;
        }

        $entryId = $this->argument('entry');
        $entry = Entry::withAnyStatus()->find($entryId);
        if (! $entry) {
            $this->error("Entry [$entryId] does not exist.");

            return self::FAILURE;
        }

        $type = $this->argument('type');
        $termType = TermType::get($type);
        if (! $termType) {
            $this->error("Term type [$type] does not exist.");

            return self::FAILURE;
        }

        if (! in_array($entry->type, $termType['entry_types'], true)) {
            $this->error("Term type [$type] is not registered for entry type [$entry->type].");

            return self::FAILURE;
        }

        return match ($action) {
            'list' => $this->list($entry, $type),
            'add' => $this->add($entry, $type),
            'remove' => $this->remove($entry, $type),
            'set' => $this->set($entry, $type),
        };
    }

    private function list(Entry $entry, string $type): int
    {
        $terms = $entry->terms()->where('type', $type)->orderBy('name')->get();
        if ($terms->isEmpty()) {
            $this->info('No terms found.');

            return self::SUCCESS;
        }

        $this->table(['id', 'slug', 'name'], $terms
            ->map(fn (Term $term): array => [$term->id, $term->slug, $term->name]));

        return self::SUCCESS;
    }

    private function add(Entry $entry, string $type): int
    {
        $terms = $this->resolveTerms($type, required: true);
        if (! $terms) {
            return self::FAILURE;
        }

        $entry->terms()->syncWithoutDetaching($terms);

        return $this->updated($entry, $type);
    }

    private function remove(Entry $entry, string $type): int
    {
        $terms = $this->resolveTerms($type, required: true);
        if (! $terms) {
            return self::FAILURE;
        }

        $entry->terms()->detach($terms);

        return $this->updated($entry, $type);
    }

    private function set(Entry $entry, string $type): int
    {
        $terms = $this->resolveTerms($type);
        if (! $terms) {
            return self::FAILURE;
        }

        $entry->terms()->sync(
            $entry->terms()->whereNot('type', $type)->pluck('id')->merge($terms->modelKeys())
        );

        return $this->updated($entry, $type);
    }

    /**
     * @return Collection<int, Term>|null
     */
    private function resolveTerms(string $type, bool $required = false): ?Collection
    {
        $slugs = $this->argument('terms');
        if (empty($slugs) && $required) {
            $this->error('No terms given.');

            return null;
        }

        $terms = Term::where('type', $type)->whereIn('slug', $slugs)->get();
        $missingSlugs = array_diff($slugs, $terms->pluck('slug')->all());
        foreach ($missingSlugs as $slug) {
            $this->error("Term [$slug] does not exist.");
        }

        return empty($missingSlugs) ? $terms : null;
    }

    private function updated(Entry $entry, string $type): int
    {
        $this->info("Updated [$type] terms of entry [$entry->id].");

        return self::SUCCESS;
    }
}
