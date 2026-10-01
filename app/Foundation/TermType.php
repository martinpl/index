<?php

namespace App\Foundation;

use App\Models\Term;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class TermType
{
    private array $types = [];

    /**
     * @param  string|class-string<Term>  $key
     * @param  array{name?: string, entry_types?: list<string>, model?: class-string<Term>}  $config
     */
    public function register(string $key, array $config = []): void
    {
        // Class lookup is case-insensitive, so key "genre" would match class "Genre".
        if (is_subclass_of($key, Term::class) && (new ReflectionClass($key))->getName() === $key) {
            $config = ['model' => $key, ...$key::config(), ...$config];
            $key = $key::$type;
        }

        if ($this->has($key)) {
            throw new DomainException("Term type [$key] is already registered.");
        }

        $this->types[$key] = [
            'key' => $key,
            'name' => $config['name'] ?? Str::headline($key),
            'entry_types' => $config['entry_types'] ?? [],
            'model' => $config['model'] ?? Term::class,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->types);
    }

    public function get(string $key): ?array
    {
        return $this->types[$key] ?? null;
    }

    public function list(): Collection
    {
        return collect($this->types)->sortKeys();
    }
}
