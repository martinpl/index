<?php

namespace App\Actions;

use App\Facades\Plugin;
use App\Foundation\PackageArchive;
use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallPlugin
{
    public function __invoke(string $source): string
    {
        $archive = PackageArchive::from($source, Plugin::storagePath());
        $temporaryPath = Plugin::storagePath(Str::uuid());

        try {
            $pluginPath = $archive->extractTo($temporaryPath);
            $plugin = Plugin::manifest($pluginPath);

            if (File::exists(Plugin::path($plugin['slug']))) {
                throw new DomainException("Plugin [{$plugin['slug']}] is already installed.");
            }

            File::ensureDirectoryExists(Plugin::path());
            File::moveDirectory($pluginPath, Plugin::path($plugin['slug']));

            return $plugin['slug'];
        } finally {
            File::deleteDirectory($temporaryPath);
            $archive->delete();
        }
    }
}
