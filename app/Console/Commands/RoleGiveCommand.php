<?php

namespace App\Console\Commands;

use App\Facades\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('role:give
    {user : The ID of the user}
    {role : The key of the role}')]
#[Description('Gives a role to a user')]
class RoleGiveCommand extends Command
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
        if (! Role::has($role)) {
            $this->error("Role [$role] does not exist.");

            return self::FAILURE;
        }

        $user->giveRole($role);
        $this->info("Gave role [$role] to user [$userId].");

        return self::SUCCESS;
    }
}
