<?php

use App\EntryTypes\Media;
use App\EntryTypes\Page;
use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'entries.media.manage']));
});

test('chooses media files of the type from the library, searching it', function () {
    $beach = Media::factory()->create(['name' => 'Beach']);
    Media::factory()->create(['name' => 'Mountains']);
    $document = Media::factory()->document()->create(['name' => 'Beach rules']);

    $picker = Livewire::test('media-picker', ['type' => 'image']);

    $picker->assertSee(['Beach', 'Mountains'])
        ->assertDontSee('Beach rules')
        ->set('search', 'bea')
        ->assertSee('Beach')
        ->assertDontSee('Mountains')
        ->call('select', $beach->id)
        ->assertSet('value', $beach->id)
        ->assertDispatched("close-dialog-{$picker->id()}")
        ->call('clear')
        ->assertSet('value', null)
        ->call('select', $document->id)
        ->assertNotFound();
});

test('uploads a file of the type to the library and chooses it', function () {
    Storage::fake('public');

    $picker = Livewire::test('media-picker', ['type' => 'image'])
        ->set('upload', UploadedFile::fake()->create('menu.pdf', 10, 'application/pdf'))
        ->assertHasErrors(['upload' => 'The file must be of the image type.'])
        ->set('upload', UploadedFile::fake()->image('beach.jpg'))
        ->assertHasNoErrors();

    expect(Media::sole()->name)->toBe('beach');
    $picker->assertSet('value', Media::sole()->id)->assertSet('upload', null);
});

test('only users who manage media can upload to the library', function () {
    Storage::fake('public');
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    Livewire::test('media-picker')
        ->assertDontSee('Drop a file to upload')
        ->set('upload', UploadedFile::fake()->image('beach.jpg'))
        ->assertForbidden();

    expect(Media::count())->toBe(0);
});

test('image blocks show the chosen image of the library', function () {
    Setting::set('active_theme', 'howdy', 'core');
    $media = Media::factory()->meta(['alt' => 'Waves on a sandy beach', 'metadata' => ['width' => 640, 'height' => 480]])->create();

    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->set('name', 'About')
        ->call('addBlock', 'image')
        ->assertSeeLivewire('media-picker')
        ->set('blocks.0.data.media', $media->id)
        ->set('values.status', EntryStatus::Publish->value)
        ->call('save')
        ->assertHasNoErrors();

    $entry = Page::sole();

    expect($entry->content->sole()->data['media'])->toBe($media->id);
    get($entry->url())->assertOk()->assertSeeInOrder(['<img', 'src="'.e($media->url()).'"', 'alt="Waves on a sandy beach"', 'width="640" height="480"'], false);

    $entry->update(['content' => [['type' => 'image', 'data' => ['media' => $media->id, 'alt' => 'Our beach']]]]);

    get($entry->url())->assertSee('alt="Our beach"', false);
});

test('image blocks only accept images of the library', function (int $media) {
    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->set('name', 'About')
        ->call('addBlock', 'image')
        ->set('blocks.0.data.media', $media)
        ->call('save')
        ->assertHasErrors(['blocks.0.data.media' => 'The selected image is invalid.']);
})->with([
    'missing media' => fn (): int => 999,
    'a document' => fn (): int => Media::factory()->document()->create()->id,
]);

test('image blocks show nothing once their image was deleted', function () {
    Setting::set('active_theme', 'howdy', 'core');
    $media = Media::factory()->create();
    $entry = Entry::factory()->published()->create(['type' => 'page', 'content' => [['type' => 'image', 'data' => ['media' => $media->id, 'caption' => 'Our beach']]]]);

    $media->deleteQuietly();

    get($entry->url())->assertOk()->assertDontSee(['<figure', 'Our beach'], false);
});
