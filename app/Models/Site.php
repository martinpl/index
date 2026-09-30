<?php

namespace App\Models;

use App\Facades\Plugin;
use App\Facades\Theme;
use Closure;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable(['domain', 'path'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $attributes = [
        'path' => '/',
    ];

    protected static function booted(): void
    {
        static::created(function (Site $site): void {
            if (! $site->isMain()) {
                $site->createTables();
            }
        });

        static::deleted(function (Site $site): void {
            if (! $site->isMain()) {
                $site->dropTables();
            }
        });
    }

    public static function current(): ?static
    {
        return app()->bound(static::class) ? app(static::class) : null;
    }

    public static function findForRequest(Request $request): ?static
    {
        $path = Str::start($request->path(), '/');

        return static::query()
            ->where('domain', $request->getHost())
            ->get()
            ->filter(fn (Site $site): bool => $path === $site->path || str_starts_with($path, rtrim($site->path, '/').'/'))
            ->sortByDesc(fn (Site $site): int => strlen($site->path))
            ->first();
    }

    public function makeCurrent(): static
    {
        app()->instance(static::class, $this);

        // Framework services read these when first resolved, so the site must be made current before that.
        config([
            'auth.passwords.users.table' => $this->tablePrefix().'password_reset_tokens',
            'cache.stores.database.table' => $this->tablePrefix().'cache',
            'cache.stores.database.lock_table' => $this->tablePrefix().'cache_locks',
            'queue.connections.database.table' => $this->tablePrefix().'jobs',
            'queue.batching.table' => $this->tablePrefix().'job_batches',
            'queue.failed.table' => $this->tablePrefix().'failed_jobs',
            'session.table' => $this->tablePrefix().'sessions',
        ]);

        Plugin::boot();
        Theme::boot();

        return $this;
    }

    public function isMain(): bool
    {
        return $this->id === 1;
    }

    public function tablePrefix(): string
    {
        return $this->isMain() ? '' : "site_{$this->id}_";
    }

    public function createTables(): void
    {
        $this->withTablePrefix(fn () => Artisan::call('migrate', ['--force' => true]));
    }

    public function dropTables(): void
    {
        $this->withTablePrefix(function (): void {
            Artisan::call('migrate:reset', ['--force' => true]);

            Schema::drop(config('database.migrations.table'));
        });
    }

    protected function withTablePrefix(Closure $callback): void
    {
        $connection = DB::connection();
        $prefix = $connection->getTablePrefix();

        $connection->setTablePrefix($prefix.$this->tablePrefix());

        try {
            $callback();
        } finally {
            $connection->setTablePrefix($prefix);
        }
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::of($value)->trim('/')->start('/')->toString(),
        );
    }
}
