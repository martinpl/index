<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ManagesMeta;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:meta
    {action : The action to perform (get, set, delete or list)}
    {user : The ID of the user}
    {key? : The meta key}
    {value? : The meta value}
    {--owner=core : The owner of the meta, e.g. core or plugins/seo}')]
#[Description('Manages the meta of a user')]
class UserMetaCommand extends Command
{
    use ManagesMeta;

    public function handle(): int
    {
        if (! $this->isSupportedMetaAction()) {
            return self::FAILURE;
        }

        $userId = $this->argument('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("User [$userId] does not exist.");

            return self::FAILURE;
        }

        return $this->manageMeta($user);
    }
}
