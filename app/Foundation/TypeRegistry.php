<?php

namespace App\Foundation;

use App\Models\Entry;
use App\Models\Term;
use DomainException;
use Illuminate\Support\Collection;
use ReflectionClass;

abstract class TypeRegistry
{
    private array $types = [];

    /**
     * @var class-string<Entry|Term>
     */
    protected string $model;

    protected string $label;

    abstract protected function attributes(string $key, array $config): array;

    /**
     * @param  string|class-string<Entry|Term>  $key
     * @param  array<string, mixed>  $config
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `plugins/blog`.
     */
    public function register(string $key, array $config, string $owner): void
    {
        // Class lookup is case-insensitive, so key "product" would match class "Product".
        if (is_subclass_of($key, $this->model) && (new ReflectionClass($key))->getName() === $key) {
            $config = ['model' => $key, ...$key::config(), ...$config];
            $key = $key::$type;
        }

        if ($existing = $this->get($key)) {
            throw new DomainException("$this->label [$key] is already registered by [{$existing['owner']}].");
        }

        $this->types[$key] = [
            'key' => $key,
            ...$this->attributes($key, $config),
            'model' => $config['model'] ?? $this->model,
            'owner' => $owner,
        ];

        $this->types[$key]['model']::registered();
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
