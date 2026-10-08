<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAbilityHolder;
use App\Facades\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ability:revoke
    {abilities* : The abilities to revoke}
    {--role= : The key of the role to revoke the abilities from}
    {--user= : The ID of the user to revoke the abilities from}')]
#[Description('Revokes abilities from a role or a user')]
class AbilityRevokeCommand extends Command
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
            $holder->revokeAbilities($abilities);
        } else {
            Role::revoke($holder, $abilities);
        }

        $this->info('Revoked abilities ['.implode(', ', $abilities).'] from '.$this->describeAbilityHolder($holder).'.');

        return self::SUCCESS;
    }
}
