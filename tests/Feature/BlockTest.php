<?php

use App\Blocks\Block;
use App\Blocks\Heading;
use App\Blocks\Missing;
use App\Blocks\Paragraph;
use App\Facades\BlockType;
use App\Facades\Filter;
use App\Fields\Text;
use App\Models\Entry;
use App\Models\Setting;

use function Pest\Laravel\get;

test('the site renders the blocks of the entry in order with their HTML attributes, escaping their text', function () {
    Setting::set('active_theme', 'index', 'core');
    $entry = Entry::factory()->published()->create(['type' => 'page', 'content' => [
        ['type' => 'heading', 'data' => ['text' => 'Welcome', 'level' => 'h3'], 'attributes' => ['id' => 'intro', 'class' => 'lead wide']],
        ['type' => 'paragraph', 'data' => ['text' => "First line\n<script>alert(1)</script>"]],
    ]]);

    get($entry->url())
        ->assertOk()
        ->assertSeeInOrder(['<h3 id="intro" class="lead wide">Welcome</h3>', 'First line<br />', '&lt;script&gt;alert(1)&lt;/script&gt;</p>'], false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('blocks are stored with their data cast by the fields of their type', function () {
    $entry = Entry::factory()->create(['content' => [
        ['type' => 'heading', 'data' => ['text' => 'Welcome', 'injected' => 'dropped']],
    ]]);

    $block = $entry->fresh()->content->first();

    expect($block)->toBeInstanceOf(Heading::class)
        ->and($block->id)->toBeString()->not->toBeEmpty()
        ->and($block->data)->toBe(['text' => 'Welcome', 'level' => 'h2']);
});

test('content given as plain text is stored as a paragraph, and as JSON as its blocks', function () {
    $text = Entry::factory()->create(['content' => 'Welcome to Index.'])->fresh();
    $notBlocks = Entry::factory()->create(['content' => '[{"text": "No type"}]'])->fresh();
    $json = Entry::factory()->create(['content' => '[{"type": "heading", "data": {"text": "Welcome"}}]'])->fresh();

    expect(json_decode($text->getRawOriginal('content'), true))->toBe($text->content->toArray())
        ->and($text->content->sole())->toBeInstanceOf(Paragraph::class)
        ->and($text->content->sole()->data)->toBe(['text' => 'Welcome to Index.'])
        ->and($notBlocks->content->sole()->data)->toBe(['text' => '[{"text": "No type"}]'])
        ->and($json->content->sole())->toBeInstanceOf(Heading::class)
        ->and($json->content->sole()->data)->toBe(['text' => 'Welcome', 'level' => 'h2']);
});

test('blocks of unregistered types are kept as they are but not rendered', function () {
    $entry = Entry::factory()->create(['content' => [
        ['id' => 'gallery', 'type' => 'gallery', 'data' => ['images' => [1, 2]]],
        ['type' => 'paragraph', 'data' => ['text' => 'Hello']],
    ]]);
    $entry->fresh()->update(['name' => 'Renamed']);

    $content = $entry->fresh()->content;

    expect($content->first())->toBeInstanceOf(Missing::class)
        ->and($content->first()->toArray())->toBe(['id' => 'gallery', 'type' => 'gallery', 'data' => ['images' => [1, 2]], 'attributes' => ['id' => null, 'class' => null]])
        ->and($content->toHtml())->toBe('<p >Hello</p>');
});

test('plugins can register block types and change the HTML of blocks', function () {
    $callout = new class extends Block
    {
        public static string $type = 'callout';

        public static function fields(): array
        {
            return [Text::make('text')];
        }

        public function render(): string
        {
            return '<aside>'.e($this->data['text'] ?? '').'</aside>';
        }
    };
    BlockType::register($callout::class, ['name' => 'Callout'], 'plugins/callouts');
    Filter::add('block.render', fn (string $html, Block $block): string => $block->type() === 'callout' ? "<div class=\"callout\">$html</div>" : $html);
    Filter::add('block.attributes.fields', fn (array $fields): array => [...$fields, Text::make('data-theme')]);

    $entry = Entry::factory()->create(['content' => [
        ['type' => 'callout', 'data' => ['text' => 'Note']],
        ['type' => 'paragraph', 'data' => ['text' => 'Hi'], 'attributes' => ['data-theme' => 'dark', 'onclick' => 'dropped']],
    ]]);

    expect($entry->fresh()->content->toHtml())->toBe("<div class=\"callout\"><aside>Note</aside></div>\n<p data-theme=\"dark\">Hi</p>")
        ->and(BlockType::get('callout'))->toMatchArray(['name' => 'Callout', 'icon' => 'lucide-square', 'owner' => 'plugins/callouts']);
});

test('registering a duplicate block type throws', function () {
    BlockType::register(Paragraph::class, [], 'plugins/blog');
})->throws(DomainException::class, 'Block type [paragraph] is already registered by [core].');
