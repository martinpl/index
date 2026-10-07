<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('user:update
    {user : The ID of the user}
    {--name= : The name of the user}
    {--email= : The email address of the user}
    {--password= : The password of the user}')]
#[Description('Updates a user')]
class UserUpdateCommand extends Command
{
    use ProfileValidationRules;

    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        $attributes = array_filter([
            'name' => $this->option('name'),
            'email' => $this->option('email'),
            'password' => $this->option('password'),
        ], fn (?string $value): bool => $value !== null);

        if (empty($attributes)) {
            $this->error('No fields to update.');

            return self::FAILURE;
        }

        $validator = Validator::make($attributes, [
            'name' => ['sometimes', ...$this->nameRules()],
            'email' => ['sometimes', ...$this->emailRules($user->id)],
            'password' => ['sometimes', 'required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $this->info("Updated user [$userId].");

        return self::SUCCESS;
    }
}
