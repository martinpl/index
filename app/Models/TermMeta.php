<?php

namespace App\Models;

use Database\Factories\TermMetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMeta extends Meta
{
    /** @use HasFactory<TermMetaFactory> */
    use HasFactory;

    protected $table = 'term_meta';

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
