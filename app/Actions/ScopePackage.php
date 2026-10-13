<?php

namespace App\Actions;

use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Illuminate\Support\php_binary;

/**
 * Prefixes the package's Composer dependencies with its namespace (using Strauss),
 * except the packages the application already provides.
 */
class ScopePackage
{
    private const straussVersion = '0.31.1';

    private const config = 'composer.strauss.json';

    public function __invoke(string $path): void
    {
        if (! File::isFile($path.'/vendor/composer/installed.json')) {
            throw new DomainException("Package [$path] has no installed dependencies, run [composer install] in it.");
        }

        $manifest = json_decode(File::get($path.'/composer.json'), true);
        $prefix = Str::studly(Str::afterLast($manifest['name'], '/'));
        $manifest['extra']['strauss'] = [
            'namespace_prefix' => $prefix.'\\Vendor\\',
            'classmap_prefix' => $prefix.'_Vendor_',
            // Prefixing global functions also renames equal strings, e.g. config keys like 'debugbar'.
            'function_prefix' => false,
            'update_call_sites' => true,
            'exclude_from_copy' => ['packages' => $this->hostPackages()],
        ];

        File::put($path.'/'.self::config, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            Process::path($path)
                ->env(['COMPOSER' => self::config])
                ->timeout(300)
                ->run([php_binary(), '-d', 'memory_limit=1G', $this->strauss()])
                ->throw();
        } finally {
            File::delete($path.'/'.self::config);
        }
    }

    /**
     * The packages required by the application, including the names they replace or provide.
     *
     * @return list<string>
     */
    private function hostPackages(): array
    {
        $installed = collect(json_decode(File::get(base_path('vendor/composer/installed.json')), true)['packages'])
            ->keyBy('name');
        $pending = array_keys(json_decode(File::get(base_path('composer.json')), true)['require']);
        $packages = [];

        while ($pending) {
            $package = $installed->get(array_pop($pending));
            if (! $package || in_array($package['name'], $packages)) {
                continue;
            }

            array_push(
                $packages,
                $package['name'],
                ...array_keys($package['replace'] ?? []),
                ...array_keys($package['provide'] ?? []),
            );
            array_push($pending, ...array_keys($package['require'] ?? []));
        }

        return array_values(array_unique($packages));
    }

    private function strauss(): string
    {
        $phar = storage_path('app/strauss-'.self::straussVersion.'.phar');

        if (! File::isFile($phar)) {
            $url = 'https://github.com/BrianHenryIE/strauss/releases/download/'.self::straussVersion.'/strauss.phar';
            File::put($phar, Http::connectTimeout(3)->timeout(60)->get($url)->throw()->body());
        }

        return $phar;
    }
}
