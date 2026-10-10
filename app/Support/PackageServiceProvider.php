<?php

namespace App\Support;

use App\Facades\EntryType;
use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class PackageServiceProvider extends ServiceProvider
{
    /**
     * The owner key of the package, e.g. `plugins/blog`, derived from the provider's location.
     */
    public static function owner(): string
    {
        $file = (new ReflectionClass(static::class))->getFileName();

        return Str::of($file)->after(base_path().'/')->explode('/')->take(2)->implode('/');
    }

    /**
     * @param  list<class-string<Entry>>  $classes
     */
    protected function addEntryTypes(array $classes): void
    {
        foreach ($classes as $class) {
            EntryType::register($class, [], static::owner());
        }
    }

    /**
     * @param  list<class-string<Term>>  $classes
     */
    protected function addTermTypes(array $classes): void
    {
        foreach ($classes as $class) {
            TermType::register($class, [], static::owner());
        }
    }
}
