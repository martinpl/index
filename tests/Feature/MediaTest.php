<?php

use App\Actions\StoreMedia;
use App\EntryTypes\Media;
use App\Enums\EntryStatus;
use App\Facades\Filter;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

test('stores files in the media library by month under a URL-friendly version of their name, with their details as meta', function () {
    Storage::fake('public');
    travelTo(now()->setDate(2026, 11, 5));
    $user = User::factory()->create();

    $media = app(StoreMedia::class)(UploadedFile::fake()->image('My Holiday Photo.jpg', 640, 480), ['alt' => 'Waves on a sandy beach', 'user_id' => $user->id]);

    expect($media->only(['type', 'status', 'slug', 'name', 'user_id', 'owner']))->toBe([
        'type' => 'media',
        'status' => EntryStatus::Internal->value,
        'slug' => 'my-holiday-photo',
        'name' => 'My Holiday Photo',
        'user_id' => $user->id,
        'owner' => 'core',
    ])
        ->and($media->filePath())->toBe('media/2026/11/my-holiday-photo.jpg')
        ->and($media->getMeta('mime_type'))->toBe('image/jpeg')
        ->and($media->getMeta('metadata'))->toBe(['filesize' => Storage::disk('public')->size('media/2026/11/my-holiday-photo.jpg'), 'width' => 640, 'height' => 480])
        ->and($media->getMeta('alt'))->toBe('Waves on a sandy beach')
        ->and($media->url())->toBe(Storage::disk('public')->url('media/2026/11/my-holiday-photo.jpg'))
        ->and($media->isImage())->toBeTrue();
});

test('suffixes the file name with the first free number when it is taken', function () {
    Storage::fake('public');
    freezeTime();
    $storeMedia = app(StoreMedia::class);

    $fileNames = collect(range(1, 3))->map(fn () => $storeMedia(UploadedFile::fake()->image('photo.png'))->fileName());

    expect($fileNames->all())->toBe(['photo.png', 'photo-2.png', 'photo-3.png']);
});

test('stores files with the extension of their contents instead of the uploaded one', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('photo.jpg');
    $path = tempnam(sys_get_temp_dir(), 'media');
    file_put_contents($path, file_get_contents($image->getRealPath()));

    $media = app(StoreMedia::class)(new UploadedFile($path, 'shell.php', test: true));

    expect($media->fileName())->toBe('shell.jpg')
        ->and($media->mimeType())->toBe('image/jpeg');
});

test('refuses files of types that may not be added to the media library', function () {
    Storage::fake('public');

    expect(fn () => app(StoreMedia::class)(UploadedFile::fake()->create('page.html', 1, 'text/html')))
        ->toThrow(DomainException::class, 'Files of type [text/html] may not be added to the media library.');

    expect(Media::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('plugins can allow more file types through the media.extensions filter', function () {
    Storage::fake('public');
    Filter::add('media.extensions', fn (array $extensions): array => [...$extensions, 'html']);

    $media = app(StoreMedia::class)(UploadedFile::fake()->create('page.html', 1, 'text/html'));

    expect($media->fileName())->toBe('page.html');
});

test('deletes media files permanently with their meta and file instead of trashing them', function () {
    Storage::fake('public');
    $media = app(StoreMedia::class)(UploadedFile::fake()->image('photo.jpg'));
    $path = $media->filePath();

    $media->delete();

    assertModelMissing($media);
    assertDatabaseMissing('entry_meta', ['entry_id' => $media->id]);
    Storage::disk('public')->assertMissing($path);
});

test('entry:delete deletes media files permanently', function () {
    Storage::fake('public');
    $media = app(StoreMedia::class)(UploadedFile::fake()->image('photo.jpg'));
    $path = $media->filePath();

    artisan('entry:delete', ['entry' => $media->id])
        ->expectsOutput("Deleted entry [$media->id].")
        ->assertSuccessful();

    assertModelMissing($media);
    Storage::disk('public')->assertMissing($path);
});

test('groups media files into types by their MIME type', function (string $mimeType, string $type) {
    $media = Media::factory()->meta(['mime_type' => $mimeType])->create();
    Media::factory()->meta(['mime_type' => $type === 'image' ? 'video/mp4' : 'image/png'])->create();

    expect($media->fileType())->toBe($type)
        ->and(Media::ofType($type)->sole()->is($media))->toBeTrue();
})->with([
    ['image/webp', 'image'],
    ['video/mp4', 'video'],
    ['audio/mpeg', 'audio'],
    ['application/pdf', 'document'],
]);

test('media files are internal entries, left out of the published entries and not served as pages', function () {
    Setting::set('active_theme', 'howdy', 'core');
    $media = Media::factory()->create(['slug' => 'beach']);

    expect(Entry::count())->toBe(0)
        ->and(Media::sole()->is($media))->toBeTrue();
    get('/media/beach')->assertNotFound();
});

test('the entry pages of media files are the media library, showing the dropzone at its create URL and editing the media file at its edit URL', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.media.manage']));
    $media = Media::factory()->create(['name' => 'Beach']);

    get(route('dashboard.entries', 'media'))
        ->assertOk()
        ->assertSeeLivewire('pages::dashboard.media')
        ->assertDontSeeLivewire('pages::dashboard.entries')
        ->assertSee(['Media Library', 'Add Media'])
        ->assertSeeHtml('x-data="{ open: false }"');
    get(route('dashboard.entries.create', 'media'))
        ->assertOk()
        ->assertSeeLivewire('pages::dashboard.media')
        ->assertSeeHtml('x-data="{ open: true }"');
    get(route('dashboard.entries.edit', ['type' => 'media', 'entry' => $media->id]))
        ->assertOk()
        ->assertSee(['Edit Media', $media->url()]);
    get(route('dashboard.entries.edit', ['type' => 'media', 'entry' => 999]))->assertNotFound();
    get(route('dashboard.home'))->assertSeeInOrder(['Media', route('dashboard.entries', 'media')]);
});

test('stores the media files of other sites in their own directory, deleting it with the site', function () {
    Storage::fake('public');
    freezeTime();
    $site = Site::create(['domain' => 'blog.example.com'])->makeCurrent();

    $media = app(StoreMedia::class)(UploadedFile::fake()->image('photo.jpg'));

    expect($media->filePath())->toBe("sites/{$site->id}/media/".now()->format('Y/m').'/photo.jpg');

    Site::find(1)->makeCurrent();
    $site->delete();

    Storage::disk('public')->assertMissing($site->mediaDirectory());
});
