<?php

namespace App\Foundation;

use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ZipArchive;

class PackageArchive
{
    private function __construct(
        private string $path,
        private bool $downloaded,
    ) {}

    public static function from(string $source, string $directory): self
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $path = $directory.'/'.Str::uuid().'.zip';
            File::ensureDirectoryExists($directory);
            File::put($path, Http::connectTimeout(3)->timeout(30)->get($source)->throw()->body());

            return new self($path, downloaded: true);
        }

        if (! File::isFile($source)) {
            throw new DomainException("Archive [$source] does not exist.");
        }

        return new self($source, downloaded: false);
    }

    public function extractTo(string $destination): string
    {
        $zip = new ZipArchive;

        if ($zip->open($this->path) !== true) {
            throw new DomainException('The archive is not a valid ZIP file.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || str_starts_with($name, '/') || str_contains($name, '../')) {
                $zip->close();

                throw new DomainException('The archive contains an invalid path.');
            }
        }

        File::ensureDirectoryExists($destination);
        $zip->extractTo($destination);
        $zip->close();

        return $this->packagePath($destination);
    }

    public function delete(): void
    {
        if ($this->downloaded) {
            File::delete($this->path);
        }
    }

    private function packagePath(string $path): string
    {
        if (File::isFile($path.'/composer.json')) {
            return $path;
        }

        $directories = File::directories($path);
        if (count($directories) === 1 && File::isFile($directories[0].'/composer.json')) {
            return $directories[0];
        }

        throw new DomainException('The archive must contain one composer.json file.');
    }
}
