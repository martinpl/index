<?php

namespace App\Console\Commands;

use App\Facades\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('role:delete {role : The key of the role}')]
#[Description('Deletes a role')]
class RoleDeleteCommand extends Command
{
    public function handle(): int
    {
        $role = $this->argument('role');
        if (! Role::has($role)) {
            $this->error("Role [$role] does not exist.");

            return self::FAILURE;
        }

        Role::delete($role);
        $this->info("Deleted role [$role].");

        return self::SUCCESS;
    }
}
