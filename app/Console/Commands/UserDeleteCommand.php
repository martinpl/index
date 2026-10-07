<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('user:delete
    {user : The ID of the user}
    {--reassign= : The ID of the user to reassign the entries to}')]
#[Description('Deletes a user')]
class UserDeleteCommand extends Command
{
    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        $reassignId = $this->option('reassign');

        $validator = Validator::make(['reassign' => $reassignId], [
            'reassign' => ['nullable', 'integer', Rule::notIn([$user->id]), Rule::exists(User::class, 'id')],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $reassignId): void {
            if ($reassignId !== null) {
                Entry::withAnyStatus()->where('user_id', $user->id)->update(['user_id' => $reassignId]);
            }

            $user->delete();
        });

        $this->info("Deleted user [$userId].");

        return self::SUCCESS;
    }
}
