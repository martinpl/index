<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('user:create
    {name : The name of the user}
    {email : The email address of the user}
    {--password= : The password of the user, generated when omitted}
    {--verified : Mark the email address of the user as verified}')]
#[Description('Creates a user')]
class UserCreateCommand extends Command
{
    use ProfileValidationRules;

    public function handle(): int
    {
        $password = $this->option('password') ?? Str::password();

        $attributes = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $password,
        ];

        $validator = Validator::make($attributes, [
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($attributes);

        if ($this->option('verified')) {
            $user->markEmailAsVerified();
        }

        $this->info("Created user [$user->id].");

        if ($this->option('password') === null) {
            $this->line("Password: $password");
        }

        return self::SUCCESS;
    }
}
