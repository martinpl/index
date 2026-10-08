<?php

namespace App\Console\Commands;

use App\Facades\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('role:create
    {role : The key of the role}
    {name? : The name of the role}')]
#[Description('Creates a role')]
class RoleCreateCommand extends Command
{
    public function handle(): int
    {
        $role = $this->argument('role');

        $validator = Validator::make(['role' => $role, 'name' => $this->argument('name')], [
            'role' => ['required', 'alpha_dash', 'lowercase', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (Role::has($role)) {
            $this->error("Role [$role] already exists.");

            return self::FAILURE;
        }

        Role::create($role, $this->argument('name'));
        $this->info("Created role [$role].");

        return self::SUCCESS;
    }
}
