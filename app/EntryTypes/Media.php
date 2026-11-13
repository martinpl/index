<?php

namespace App\EntryTypes;

use App\Enums\EntryStatus;
use App\Facades\Filter;
use App\Models\Entry;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * A file in the media library, like an image or a document, kept as an entry with the path of the file as its content,
 * and the details of the file in its meta, like `mime_type`, `metadata` (its `filesize`, and the `width` and `height` of images) and `alt`.
 *
 * Media files always have the `internal` status, so they are not served as pages nor counted as the entries of their authors.
 *
 * @property string|null $content
 *
 * @method static Builder<static> ofType(string $type)
 */
#[UseFactory(MediaFactory::class)]
class Media extends Entry
{
    public static string $type = 'media';

    /**
     * The disk the media files are stored on.
     */
    const DISK = 'public';

    /**
     * The types media files are grouped by, matching the first part of their MIME type, with documents for the rest.
     */
    const TYPES = ['image', 'video', 'audio', 'document'];

    protected $attributes = [
        'status' => EntryStatus::Internal->value,
        'order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [...parent::casts(), 'content' => 'json'];
    }

    /**
     * @return array{name?: string, slug?: string, icon?: string, can?: string, listing?: string, editor?: string}
     */
    public static function config(): array
    {
        return [
            'name' => 'Media',
            'icon' => 'lucide-images',
            'can' => 'entries.media.manage',
            'listing' => 'pages::dashboard.media',
            'editor' => 'pages::dashboard.media',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        // Replaces the scope to published entries, as media files are internal entries.
        static::addGlobalScope('published', function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), EntryStatus::Internal);
        });
    }

    /**
     * The extensions of the files that may be added to the media library, which plugins may change through the `media.extensions` filter.
     *
     * Files are stored with the extension guessed from their contents, so a file can only be added when that extension is listed.
     *
     * @return list<string>
     */
    public static function extensions(): array
    {
        return Filter::apply('media.extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif',
            'mp4', 'webm', 'mov',
            'mp3', 'wav', 'ogg', 'm4a',
            'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'zip',
        ]);
    }

    /**
     * The validation rules of a file uploaded to the media library.
     *
     * @return list<string>
     */
    public static function uploadRules(): array
    {
        return ['required', 'file', 'mimes:'.implode(',', static::extensions())];
    }

    /**
     * The path of the file on the media disk, which is the content of the media file.
     */
    public function filePath(): ?string
    {
        return $this->content;
    }

    public function fileName(): string
    {
        return basename((string) $this->filePath());
    }

    /**
     * The URL of the file, as media files have no page of their own.
     */
    public function url(): string
    {
        return Storage::disk(self::DISK)->url((string) $this->filePath());
    }

    public function mimeType(): string
    {
        return $this->getMeta('mime_type', 'application/octet-stream');
    }

    /**
     * The size of the file in bytes.
     */
    public function fileSize(): ?int
    {
        return $this->getMeta('metadata', [])['filesize'] ?? null;
    }

    public function width(): ?int
    {
        return $this->getMeta('metadata', [])['width'] ?? null;
    }

    public function height(): ?int
    {
        return $this->getMeta('metadata', [])['height'] ?? null;
    }

    /**
     * The alternative text describing the image for people who cannot see it.
     */
    public function alt(): ?string
    {
        return $this->getMeta('alt');
    }

    /**
     * The type of the media file, one of the `TYPES`.
     */
    public function fileType(): string
    {
        return static::typeOf($this->mimeType());
    }

    public function isImage(): bool
    {
        return $this->fileType() === 'image';
    }

    /**
     * The type of files of the given MIME type, one of the `TYPES`.
     */
    public static function typeOf(string $mimeType): string
    {
        $type = strstr($mimeType, '/', true);

        return in_array($type, ['image', 'video', 'audio'], true) ? $type : 'document';
    }

    /**
     * Delete the media file with its file, as media files are not trashed but deleted permanently.
     */
    protected function performDeleteOnModel(): void
    {
        $path = $this->filePath();

        $this->forceDeleting = true;
        parent::performDeleteOnModel();
        $this->forceDeleting = false;

        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Scope to media files of the given type, one of the `TYPES`.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        if ($type !== 'document') {
            $query->whereMeta('mime_type', 'like', "$type/%");

            return;
        }

        foreach (['image', 'video', 'audio'] as $other) {
            $query->whereMeta('mime_type', 'not like', "$other/%");
        }
    }
}
