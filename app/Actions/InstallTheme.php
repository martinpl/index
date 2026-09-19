<?php

namespace App\Actions;

use App\Facades\Theme;
use App\Foundation\PackageArchive;
use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallTheme
{
    public function __invoke(string $source): string
    {
        $archive = PackageArchive::from($source, Theme::storagePath());
        $temporaryPath = Theme::storagePath(Str::uuid());

        try {
            $themePath = $archive->extractTo($temporaryPath);
            $theme = Theme::manifest($themePath);

            if (File::exists(Theme::path($theme['slug']))) {
                throw new DomainException("Theme [{$theme['slug']}] is already installed.");
            }

            File::ensureDirectoryExists(Theme::path());
            File::moveDirectory($themePath, Theme::path($theme['slug']));

            return $theme['slug'];
        } finally {
            File::deleteDirectory($temporaryPath);
            $archive->delete();
        }
    }
}
