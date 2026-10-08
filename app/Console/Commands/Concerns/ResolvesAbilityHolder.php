<?php

namespace App\Console\Commands\Concerns;

use App\Facades\Role;
use App\Models\User;

trait ResolvesAbilityHolder
{
    /**
     * Resolve the role key or the user given by the "--role" or "--user" option.
     */
    protected function resolveAbilityHolder(): User|string|null
    {
        $role = $this->option('role');
        $userId = $this->option('user');

        if (($role === null) === ($userId === null)) {
            $this->error('Either the --role or the --user option is required.');

            return null;
        }

        if ($role !== null) {
            if (! Role::has($role)) {
                $this->error("Role [$role] does not exist.");

                return null;
            }

            return $role;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return null;
        }

        return $user;
    }

    protected function describeAbilityHolder(User|string $holder): string
    {
        return $holder instanceof User ? "user [$holder->id]" : "role [$holder]";
    }
}
