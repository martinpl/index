<?php

namespace App\Console\Commands;

use App\Actions\StoreMedia;
use App\Models\User;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('media:import
    {files* : The paths of the files to add to the media library}
    {--alt= : The alternative text of the imported images}
    {--user= : The ID of the user who uploaded the files}
    {--owner=core : The owner of the media files, e.g. core or plugins/seo}')]
#[Description('Adds files to the media library')]
class MediaImportCommand extends Command
{
    public function handle(StoreMedia $storeMedia): int
    {
        $attributes = [
            'alt' => $this->option('alt'),
            'user_id' => $this->option('user'),
            'owner' => $this->option('owner'),
        ];

        $validator = Validator::make($attributes, [
            'alt' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'owner' => ['required', 'string', 'max:255'],
        ], attributes: ['user_id' => 'user']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $attributes['user_id'] = filled($attributes['user_id']) ? (int) $attributes['user_id'] : null;
        $exitCode = self::SUCCESS;

        foreach ($this->argument('files') as $path) {
            if (! is_file($path)) {
                $this->error("File [$path] does not exist.");
                $exitCode = self::FAILURE;

                continue;
            }

            try {
                $media = $storeMedia(new File($path), $attributes);
            } catch (DomainException $exception) {
                $this->error($exception->getMessage());
                $exitCode = self::FAILURE;

                continue;
            }

            $this->info("Imported [$path] as media file [$media->id].");
        }

        return $exitCode;
    }
}
