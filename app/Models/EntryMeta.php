<?php

namespace App\Models;

use Database\Factories\EntryMetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryMeta extends Meta
{
    /** @use HasFactory<EntryMetaFactory> */
    use HasFactory;

    protected $table = 'entry_meta';

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }
}
