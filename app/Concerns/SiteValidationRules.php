<?php

namespace App\Concerns;

use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait SiteValidationRules
{
    /**
     * Get the validation rules used to validate site domains, which are unique along with the given path.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function domainRules(?string $path, ?int $siteId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i',
            Rule::unique(Site::class, 'domain')->where('path', Site::make(['path' => $path ?? '/'])->path)->ignore($siteId),
        ];
    }

    /**
     * Get the validation rules used to validate site paths.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function pathRules(): array
    {
        return ['required', 'string', 'max:255'];
    }
}
