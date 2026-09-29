<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['type', 'slug', 'name', 'description', 'parent_id'])]
class Term extends Model
{
    use BelongsToSite;

    /** @use HasFactory<TermFactory> */
    use HasFactory;

    public $timestamps = false;

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, $this->siteTable('entry_term'));
    }
}
