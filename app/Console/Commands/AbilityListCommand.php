<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAbilityHolder;
use App\Facades\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ability:list
    {--role= : The key of the role to list the abilities of}
    {--user= : The ID of the user to list the abilities of, including those granted through roles}')]
#[Description('Lists the abilities of a role or a user')]
class AbilityListCommand extends Command
{
    use ResolvesAbilityHolder;

    public function handle(): int
    {
        $holder = $this->resolveAbilityHolder();
        if ($holder === null) {
            return self::FAILURE;
        }

        $abilities = $holder instanceof User
            ? $holder->getAbilities()
            : collect(Role::abilities($holder))->sort()->values()->all();

        if (empty($abilities)) {
            $this->info('No abilities found.');

            return self::SUCCESS;
        }

        $this->table(['ability'], array_map(fn (string $ability): array => [$ability], $abilities));

        return self::SUCCESS;
    }
}
