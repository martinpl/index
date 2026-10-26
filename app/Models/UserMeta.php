<?php

namespace App\Models;

use Database\Factories\UserMetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Meta
{
    /** @use HasFactory<UserMetaFactory> */
    use HasFactory;

    protected $table = 'user_meta';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
