<?php

namespace App\Actions;

use App\Facades\Plugin;
use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ZipArchive;

class InstallPlugin
{
    public function __invoke(string $source): string
    {
        $archive = $this->archive($source);
        $temporaryPath = storage_path('app/plugins/'.Str::uuid());

        File::ensureDirectoryExists($temporaryPath);

        try {
            $this->extract($archive, $temporaryPath);
            $pluginPath = $this->extractedPluginPath($temporaryPath);
            $plugin = Plugin::manifest($pluginPath);

            if (! $plugin['slug']) {
                throw new DomainException("Plugin [$pluginPath/composer.json] requires a package name.");
            }

            $destination = base_path('plugins/'.$plugin['slug']);
            if (File::exists($destination)) {
                throw new DomainException("Plugin [{$plugin['slug']}] is already installed.");
            }

            File::ensureDirectoryExists(base_path('plugins'));
            File::moveDirectory($pluginPath, $destination);

            return $plugin['slug'];
        } finally {
            File::deleteDirectory($temporaryPath);

            if (str_starts_with($archive, storage_path('app/plugins/'))) {
                File::delete($archive);
            }
        }
    }

    private function archive(string $source): string
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $archive = storage_path('app/plugins/'.Str::uuid().'.zip');
            File::ensureDirectoryExists(dirname($archive));
            File::put($archive, Http::connectTimeout(3)->timeout(30)->get($source)->throw()->body());

            return $archive;
        }

        if (! File::isFile($source)) {
            throw new DomainException("Plugin archive [$source] does not exist.");
        }

        return $source;
    }

    private function extract(string $archive, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new DomainException('The plugin archive is not a valid ZIP file.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || str_starts_with($name, '/') || str_contains($name, '../')) {
                $zip->close();

                throw new DomainException('The plugin archive contains an invalid path.');
            }
        }

        $zip->extractTo($destination);
        $zip->close();
    }

    private function extractedPluginPath(string $path): string
    {
        if (File::isFile($path.'/composer.json')) {
            return $path;
        }

        $directories = File::directories($path);
        if (count($directories) === 1 && File::isFile($directories[0].'/composer.json')) {
            return $directories[0];
        }

        throw new DomainException('The plugin archive must contain one composer.json file.');
    }
}
