<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:list')]
#[Description('Lists users')]
class UserListCommand extends Command
{
    public function handle(): int
    {
        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return self::SUCCESS;
        }

        $this->table(['id', 'name', 'email', 'created_at'], $users
            ->map(fn (User $user): array => [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at,
            ]));

        return self::SUCCESS;
    }
}
