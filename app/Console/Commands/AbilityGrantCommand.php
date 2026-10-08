<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAbilityHolder;
use App\Facades\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ability:grant
    {abilities* : The abilities to grant}
    {--role= : The key of the role to grant the abilities to}
    {--user= : The ID of the user to grant the abilities to}')]
#[Description('Grants abilities to a role or a user')]
class AbilityGrantCommand extends Command
{
    use ResolvesAbilityHolder;

    public function handle(): int
    {
        $holder = $this->resolveAbilityHolder();
        if ($holder === null) {
            return self::FAILURE;
        }

        $abilities = $this->argument('abilities');

        if ($holder instanceof User) {
            $holder->grantAbilities($abilities);
        } else {
            Role::grant($holder, $abilities);
        }

        $this->info('Granted abilities ['.implode(', ', $abilities).'] to '.$this->describeAbilityHolder($holder).'.');

        return self::SUCCESS;
    }
}
