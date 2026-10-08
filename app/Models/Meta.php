<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasOwner;
use Database\Factories\MetaFactory;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

#[Fillable(['key', 'value', 'owner'])]
class Meta extends Model
{
    use BelongsToSite;

    /** @use HasFactory<MetaFactory> */
    use HasFactory;

    use HasOwner;

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

    /**
     * Scope to meta whose decoded value matches the given comparison.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereValue(Builder $query, string $operator, mixed $value): void
    {
        // Laravel's "->" JSON selectors cannot address the root of a JSON column.
        $query->where(new class($query->qualifyColumn('value')) implements Expression
        {
            public function __construct(protected string $column) {}

            public function getValue(Grammar $grammar): string
            {
                $column = $grammar->wrap($this->column);

                return match (true) {
                    $grammar instanceof SQLiteGrammar => "json_extract($column, '$')",
                    $grammar instanceof PostgresGrammar => "($column #>> '{}')",
                    default => "json_unquote(json_extract($column, '$'))",
                };
            }
        }, $operator, $value);
    }
}
