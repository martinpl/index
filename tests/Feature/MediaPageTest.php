<?php

use App\Actions\StoreMedia;
use App\EntryTypes\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create(['name' => 'Me']))->grantAbilities(['entries.media.manage']));
});

function mediaPage(): Testable
{
    return Livewire::test('pages::dashboard.media');
}

test('users without the ability cannot manage media', function () {
    get(route('dashboard.entries', 'media'))->assertOk();
    get(route('dashboard.home'))->assertSee(route('dashboard.entries', 'media'));

    actingAs(User::factory()->create());

    get(route('dashboard.entries', 'media'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.entries', 'media'));
});

test('uploads the files to the media library as the current user', function () {
    Storage::fake('public');

    mediaPage()
        ->set('uploads', [UploadedFile::fake()->image('beach.jpg'), UploadedFile::fake()->create('menu.pdf', 10, 'application/pdf')])
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: '2 files uploaded.')
        ->assertSet('uploads', [])
        ->assertSee(['beach', 'menu.pdf']);

    $mediaItems = Media::orderBy('id')->get();

    expect($mediaItems->map->mimeType()->all())->toBe(['image/jpeg', 'application/pdf'])
        ->and($mediaItems->pluck('user_id')->unique()->all())->toBe([auth()->id()]);
    $mediaItems->each(fn (Media $media) => Storage::disk('public')->assertExists($media->filePath()));
});

test('refuses uploads of types that may not be added to the media library', function () {
    Storage::fake('public');

    mediaPage()
        ->set('uploads', [UploadedFile::fake()->create('page.html', 1, 'text/html')])
        ->assertHasErrors(['uploads.0' => 'mimes']);

    expect(Media::count())->toBe(0);
});

test('lists the media files newest first, with a tab for each type', function () {
    Media::factory()->create(['name' => 'Old photo', 'date' => now()->subDay()]);
    Media::factory()->create(['name' => 'New photo']);
    Media::factory()->document()->create(['name' => 'Price list']);

    mediaPage()
        ->assertSeeInOrder(['Price list', 'New photo', 'Old photo'])
        ->assertSeeText(['Images', 'Documents'])
        ->assertDontSeeText('Videos')
        ->set('tab', 'document')
        ->assertSee('Price list')
        ->assertDontSee('New photo');
});

test('escapes the names of the media files', function () {
    Media::factory()->create(['name' => '<script>alert(1)</script>']);

    mediaPage()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('edits the name and alternative text of an image', function () {
    $media = Media::factory()->create(['name' => 'IMG_0001', 'slug' => 'img_0001']);

    mediaPage()
        ->call('runAction', 'edit', $media->id)
        ->assertSet('editing', $media->id)
        ->assertSet('values', ['name' => 'IMG_0001', 'alt' => null])
        ->assertSee($media->url())
        ->set('values.name', '')
        ->call('save')
        ->assertHasErrors(['values.name' => 'required'])
        ->set('values.name', 'Beach')
        ->set('values.alt', 'Waves on a sandy beach')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Media updated.')
        ->assertSet('editing', null);

    assertDatabaseHas('entries', ['id' => $media->id, 'name' => 'Beach', 'slug' => 'img_0001']);
    expect($media->alt())->toBe('Waves on a sandy beach');
});

test('edits only the name of media files other than images', function () {
    $media = Media::factory()->document()->create();

    mediaPage()
        ->call('runAction', 'edit', $media->id)
        ->assertSet('values', ['name' => $media->name])
        ->set('values.alt', 'Ignored')
        ->set('values.name', 'Price list')
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('entries', ['id' => $media->id, 'name' => 'Price list']);
    expect($media->hasMeta('alt'))->toBeFalse();
});

test('deletes the selected media with their files', function () {
    Storage::fake('public');
    $storeMedia = app(StoreMedia::class);
    [$first, $second, $kept] = collect(['a.jpg', 'b.jpg', 'c.jpg'])->map(fn (string $name) => $storeMedia(UploadedFile::fake()->image($name)));
    $paths = [$first->filePath(), $second->filePath()];

    mediaPage()
        ->call('runAction', 'edit', $first->id)
        ->set('selected', [(string) $first->id, (string) $second->id])
        ->call('runAction', 'delete')
        ->assertDispatched('toast', title: '2 files deleted.')
        ->assertSet('editing', null);

    assertModelMissing($first);
    assertModelMissing($second);
    assertModelExists($kept);
    Storage::disk('public')->assertMissing($paths);
    Storage::disk('public')->assertExists($kept->filePath());
});
