<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:get {user : The ID of the user}')]
#[Description('Gets a user')]
class UserGetCommand extends Command
{
    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        $this->table(['field', 'value'], [
            ['id', $user->id],
            ['name', $user->name],
            ['email', $user->email],
            ['email_verified_at', $user->email_verified_at],
            ['created_at', $user->created_at],
            ['updated_at', $user->updated_at],
        ]);

        return self::SUCCESS;
    }
}
