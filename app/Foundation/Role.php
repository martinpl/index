<?php

namespace App\Foundation;

use App\Models\Option;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Role
{
    /**
     * @return array<string, array{name: string, abilities: list<string>}>
     */
    protected function roles(): array
    {
        return Option::get('roles', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->roles());
    }

    /**
     * @return array{key: string, name: string, abilities: list<string>}|null
     */
    public function get(string $key): ?array
    {
        $role = $this->roles()[$key] ?? null;

        return $role ? ['key' => $key, ...$role] : null;
    }

    /**
     * @return Collection<string, array{key: string, name: string, abilities: list<string>}>
     */
    public function list(): Collection
    {
        return collect($this->roles())
            ->map(fn (array $role, string $key): array => ['key' => $key, ...$role])
            ->sortKeys();
    }

    public function create(string $key, ?string $name = null): void
    {
        if ($this->has($key)) {
            throw new DomainException("Role [$key] already exists.");
        }

        $this->save([...$this->roles(), $key => [
            'name' => $name ?? Str::headline($key),
            'abilities' => [],
        ]]);
    }

    public function delete(string $key): void
    {
        $roles = $this->roles();

        unset($roles[$key]);

        $this->save($roles);

        User::whereMetaContains('roles', $key)->each(fn (User $user) => $user->revokeRole($key));
    }

    /**
     * @return list<string>
     */
    public function abilities(string $key): array
    {
        return $this->roles()[$key]['abilities'] ?? [];
    }

    /**
     * @param  array<int, string>  $abilities
     */
    public function grant(string $key, array $abilities): void
    {
        $this->setAbilities($key, array_unique([...$this->abilities($key), ...$abilities]));
    }

    /**
     * @param  array<int, string>  $abilities
     */
    public function revoke(string $key, array $abilities): void
    {
        $this->setAbilities($key, array_diff($this->abilities($key), $abilities));
    }

    /**
     * @param  array<int, string>  $abilities
     */
    protected function setAbilities(string $key, array $abilities): void
    {
        $roles = $this->roles();

        $role = $roles[$key] ?? throw new DomainException("Role [$key] does not exist.");

        $roles[$key] = ['name' => $role['name'], 'abilities' => array_values($abilities)];

        $this->save($roles);
    }

    /**
     * @param  array<string, array{name: string, abilities: list<string>}>  $roles
     */
    protected function save(array $roles): void
    {
        Option::set('roles', $roles, 'core');
    }
}
