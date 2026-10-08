<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('role:revoke
    {user : The ID of the user}
    {role : The key of the role}')]
#[Description('Revokes a role from a user')]
class RoleRevokeCommand extends Command
{
    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        $role = $this->argument('role');
        if (! $user->hasRole($role)) {
            $this->error("User [$userId] does not have role [$role].");

            return self::FAILURE;
        }

        $user->revokeRole($role);
        $this->info("Revoked role [$role] from user [$userId].");

        return self::SUCCESS;
    }
}
