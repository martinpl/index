<?php

namespace App\Foundation;

use App\Models\Entry;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class EntryType
{
    private array $types = [];

    /**
     * @param  string|class-string<Entry>  $key
     * @param  array{name?: string, slug?: string, model?: class-string<Entry>}  $config
     */
    public function register(string $key, array $config = []): void
    {
        // Class lookup is case-insensitive, so key "product" would match class "Product".
        if (is_subclass_of($key, Entry::class) && (new ReflectionClass($key))->getName() === $key) {
            $config = ['model' => $key, ...$key::config(), ...$config];
            $key = $key::$type;
        }

        if ($this->has($key)) {
            throw new DomainException("Entry type [$key] is already registered.");
        }

        $this->types[$key] = [
            'key' => $key,
            'name' => $config['name'] ?? Str::headline($key),
            'slug' => $config['slug'] ?? Str::plural($key),
            'model' => $config['model'] ?? Entry::class,
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
