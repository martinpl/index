<?php

namespace App\Models\Concerns;

use App\Facades\Role;

trait HasRoles
{
    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->getMeta('roles', []);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function giveRole(string $role): void
    {
        $this->setMeta('roles', array_values(array_unique([...$this->getRoles(), $role])));
    }

    public function revokeRole(string $role): void
    {
        $this->setMeta('roles', array_values(array_diff($this->getRoles(), [$role])));
    }

    /**
     * Get the abilities granted to the user directly and through roles.
     *
     * @return list<string>
     */
    public function getAbilities(): array
    {
        return collect($this->getRoles())
            ->flatMap(fn (string $role): array => Role::abilities($role))
            ->merge($this->getMeta('abilities', []))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->getAbilities());
    }

    /**
     * @param  array<int, string>  $abilities
     */
    public function grantAbilities(array $abilities): void
    {
        $this->setMeta('abilities', array_values(array_unique([...$this->getMeta('abilities', []), ...$abilities])));
    }

    /**
     * @param  array<int, string>  $abilities
     */
    public function revokeAbilities(array $abilities): void
    {
        $this->setMeta('abilities', array_values(array_diff($this->getMeta('abilities', []), $abilities)));
    }
}
