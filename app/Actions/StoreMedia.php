<?php

namespace App\Actions;

use App\EntryTypes\Media;
use App\Models\Site;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

class StoreMedia
{
    /**
     * Add the file to the media library of the current site, stored by month under a URL-friendly version of its name.
     *
     * @param  array{name?: string, alt?: string|null, user_id?: int|null, parent_id?: int|null, owner?: string}  $attributes
     *
     * @throws DomainException When the extension guessed from the file's contents may not be added to the media library.
     */
    public function __invoke(File $file, array $attributes = []): Media
    {
        $extension = $file->guessExtension();

        if (! in_array($extension, Media::extensions(), true)) {
            throw new DomainException("Files of type [{$file->getMimeType()}] may not be added to the media library.");
        }

        $originalName = pathinfo($file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename(), PATHINFO_FILENAME);
        $directory = (Site::current()?->mediaDirectory() ?? 'media').'/'.now()->format('Y/m');
        $path = Storage::disk(Media::DISK)->putFileAs($directory, $file, $this->uniqueFileName($directory, Str::slug($originalName) ?: 'file', $extension));

        throw_unless($path, DomainException::class, "The file [$originalName] could not be stored.");

        $mimeType = $file->getMimeType();
        $dimensions = str_starts_with($mimeType, 'image/') ? getimagesize($file->getRealPath()) : false;
        [$width, $height] = $dimensions ?: [null, null];

        $media = Media::create(['name' => $originalName, 'owner' => 'core', ...Arr::except($attributes, 'alt'), 'content' => $path]);

        $media->setMeta('mime_type', $mimeType, 'core');
        $media->setMeta('metadata', array_filter(['filesize' => $file->getSize(), 'width' => $width, 'height' => $height], filled(...)), 'core');
        $media->setMeta('alt', $attributes['alt'] ?? null, 'core');

        return $media;
    }

    /**
     * The file name, suffixed with the first free number when the directory has a file of that name, e.g. `photo-2.jpg`.
     */
    protected function uniqueFileName(string $directory, string $name, string $extension): string
    {
        $candidate = "$name.$extension";

        for ($number = 2; Storage::disk(Media::DISK)->exists("$directory/$candidate"); $number++) {
            $candidate = "$name-$number.$extension";
        }

        return $candidate;
    }
}
