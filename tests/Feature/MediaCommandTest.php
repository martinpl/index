<?php

use App\EntryTypes\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

/**
 * Write a file with the given name and contents to a temporary directory, returning its path.
 */
function mediaFile(string $name, string $contents): string
{
    $directory = sys_get_temp_dir().'/media-command-test';
    File::ensureDirectoryExists($directory);
    File::put("$directory/$name", $contents);

    return "$directory/$name";
}

afterEach(function () {
    File::deleteDirectory(sys_get_temp_dir().'/media-command-test');
});

test('imports files into the media library', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('beach.jpg', 640, 480);
    $path = mediaFile('beach.jpg', $image->getContent());

    artisan('media:import', ['files' => [$path], '--alt' => 'A sandy beach', '--user' => $user->id, '--owner' => 'plugins/importer'])
        ->expectsOutput("Imported [$path] as media file [1].")
        ->assertSuccessful();

    $media = Media::sole();

    expect($media->only(['name', 'user_id', 'owner']))->toBe(['name' => 'beach', 'user_id' => $user->id, 'owner' => 'plugins/importer'])
        ->and([$media->alt(), $media->mimeType(), $media->width(), $media->height()])->toBe(['A sandy beach', 'image/jpeg', 640, 480]);
    Storage::disk('public')->assertExists($media->filePath());
    expect($path)->toBeFile();
});

test('imports the valid files and reports the others', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('beach.jpg');
    $valid = mediaFile('beach.jpg', $image->getContent());
    $html = mediaFile('page.html', '<!DOCTYPE html><html><body>Hello</body></html>');

    artisan('media:import', ['files' => ['missing.jpg', $html, $valid]])
        ->expectsOutput('File [missing.jpg] does not exist.')
        ->expectsOutput('Files of type [text/html] may not be added to the media library.')
        ->expectsOutput("Imported [$valid] as media file [1].")
        ->assertFailed();

    expect(Media::count())->toBe(1);
});

test('does not import files for an unknown user', function () {
    artisan('media:import', ['files' => ['beach.jpg'], '--user' => 999])
        ->expectsOutput('The selected user is invalid.')
        ->assertFailed();
});
