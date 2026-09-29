<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Database\Factories\MetaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['key', 'value'])]
class Meta extends Model
{
    use BelongsToSite;

    /** @use HasFactory<MetaFactory> */
    use HasFactory;

    protected $table = 'meta';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function metable(): MorphTo
    {
        return $this->morphTo();
    }
}
