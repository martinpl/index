<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasOwner
{
    /**
     * Scope to models owned by the given package, e.g. "plugins/blog".
     */
    #[Scope]
    protected function ownedBy(Builder $query, string $owner): void
    {
        $query->where($query->qualifyColumn('owner'), $owner);
    }
}
