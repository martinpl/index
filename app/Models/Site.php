<?php

namespace App\Models;

use App\Entries\Media;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

                Storage::disk(Media::DISK)->deleteDirectory($site->mediaDirectory());
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

    /**
     * Serve the request from the site's path, as if the app was installed in that directory, so every
     * route matches below it and generated URLs include it, while assets keep their public root.
     */
    public function mount(Request $request): static
    {
        if ($this->path === '/') {
            return $this;
        }

        URL::useAssetOrigin(config('app.asset_url') ?? $request->root());

        $baseUrl = $request->getBaseUrl().$this->path;

        (function () use ($baseUrl): void {
            $this->baseUrl = $baseUrl;
            $this->basePath = $this->pathInfo = null;
        })->call($request);

        return $this;
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
            'session.path' => $this->path,
        ]);

        app()->setLocale(Setting::get('locale', config('app.locale')));

        Plugin::boot();
        Theme::boot();

        return $this;
    }

    /**
     * Run the callback with the site's tables, without booting its plugins and theme.
     */
    public function execute(Closure $callback): mixed
    {
        $previous = static::current();
        app()->instance(static::class, $this);

        try {
            return $callback($this);
        } finally {
            $previous ? app()->instance(static::class, $previous) : app()->forgetInstance(static::class);
        }
    }

    public function isMain(): bool
    {
        return $this->id === 1;
    }

    /**
     * Get the value of the site's setting.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->execute(fn (): mixed => Setting::get($key, $default));
    }

    /**
     * The URL of the given path on the site, keeping the scheme and port of the current request.
     */
    public function url(string $path = ''): string
    {
        $root = Str::replaceFirst(request()->getHost(), $this->domain, request()->getSchemeAndHttpHost());

        return rtrim($root.rtrim($this->path, '/').Str::start($path, '/'), '/');
    }

    public function tablePrefix(): string
    {
        return $this->isMain() ? '' : "site_{$this->id}_";
    }

    /**
     * The directory of the site's media library on the media disk.
     */
    public function mediaDirectory(): string
    {
        return $this->isMain() ? 'media' : "sites/{$this->id}/media";
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
