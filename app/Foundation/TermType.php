<?php

namespace App\Foundation;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TermType
{
    private array $types = [];

    public function register(string $key, array $attributes = []): void
    {
        if ($this->has($key)) {
            throw new DomainException("Term type [$key] is already registered.");
        }

        $this->types[$key] = [
            'key' => $key,
            'name' => $attributes['name'] ?? Str::headline($key),
            'entry_types' => $attributes['entry_types'] ?? [],
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
