<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

#[Signature('user:reset-password
    {user : The ID of the user}
    {--skip-email : Do not send the password reset link to the user}
    {--show-password : Show the new password}')]
#[Description('Resets the password of a user')]
class UserResetPasswordCommand extends Command
{
    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        $password = Str::password();

        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $this->info("Reset password of user [$userId].");

        if ($this->option('show-password')) {
            $this->line("Password: $password");
        }

        if (! $this->option('skip-email')) {
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status !== Password::RESET_LINK_SENT) {
                $this->error(__($status));

                return self::FAILURE;
            }

            $this->info("Sent password reset link to user [$userId].");
        }

        return self::SUCCESS;
    }
}
