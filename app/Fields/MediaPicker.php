<?php

namespace App\Fields;

use App\EntryTypes\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Holds the ID of the media file chosen from, or uploaded to, the media library.
 */
class MediaPicker extends Field
{
    public string $component = 'fields.media-picker';

    protected ?string $type = null;

    /**
     * Only allow choosing media files of the given type, one of `Media::TYPES`, like `image`.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRules(string $path): array
    {
        return [$path => ['nullable', 'integer', new class($this->type) implements ValidationRule
        {
            public function __construct(protected ?string $type) {}

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                $exists = Media::query()
                    ->when($this->type, fn (Builder $query, string $type) => $query->ofType($type))
                    ->whereKey($value)
                    ->exists();

                if (! $exists) {
                    $fail('validation.exists')->translate();
                }
            }
        }, ...$this->rules]];
    }

    public function cast(mixed $value): ?int
    {
        return blank($value) ? null : (int) $value;
    }
}
