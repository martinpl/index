<?php

namespace App\Console\Commands;

use App\Facades\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('role:list')]
#[Description('Lists roles')]
class RoleListCommand extends Command
{
    public function handle(): int
    {
        $roles = Role::list();
        if ($roles->isEmpty()) {
            $this->info('No roles found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'name'], $roles
            ->map(fn (array $role): array => [$role['key'], $role['name']]));

        return self::SUCCESS;
    }
}
