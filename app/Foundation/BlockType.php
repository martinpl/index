<?php

namespace App\Foundation;

use App\Blocks\Block;
use App\Blocks\Missing;
use App\Facades\Filter;
use App\Schema\Component;
use App\Schema\Group;
use App\Schema\Text;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BlockType
{
    /**
     * @var array<string, array{key: string, name: string, icon: string, description: string|null, entry_types: list<string>|null, class: class-string<Block>, owner: string}>
     */
    protected array $types = [];

    /**
     * Register the block type, which the editors of every entry type offer, unless its `entry_types` limit it to the editors
     * of the given ones, like the content block to layouts.
     *
     * @param  class-string<Block>  $class
     * @param  array{name?: string, icon?: string, description?: string, entry_types?: list<string>}  $config
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `plugins/blog`.
     */
    public function register(string $class, array $config, string $owner): void
    {
        $config = [...$class::config(), ...$config];
        $key = $class::$type;

        if ($existing = $this->get($key)) {
            throw new DomainException("Block type [$key] is already registered by [{$existing['owner']}].");
        }

        $this->types[$key] = [
            'key' => $key,
            'name' => $config['name'] ?? Str::headline($key),
            'icon' => $config['icon'] ?? 'lucide-square',
            'description' => $config['description'] ?? null,
            'entry_types' => $config['entry_types'] ?? null,
            'class' => $class,
            'owner' => $owner,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->types);
    }

    /**
     * @return array{key: string, name: string, icon: string, description: string|null, entry_types: list<string>|null, class: class-string<Block>, owner: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->types[$key] ?? null;
    }

    /**
     * The block types in the order they were registered, which is the order the block editor offers them in.
     *
     * @return Collection<string, array{key: string, name: string, icon: string, description: string|null, entry_types: list<string>|null, class: class-string<Block>, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->types);
    }

    /**
     * The block types the editor of the given entry type offers, in the order they were registered.
     *
     * @return Collection<string, array{key: string, name: string, icon: string, description: string|null, entry_types: list<string>|null, class: class-string<Block>, owner: string}>
     */
    public function for(string $entryType): Collection
    {
        return $this->list()->filter(fn (array $type): bool => in_array($entryType, $type['entry_types'] ?? [$entryType], true));
    }

    /**
     * The visible components of the block type, which plugins may change through the `blocks.{key}.schema` filter.
     *
     * @return list<Component>
     */
    public function schema(string $key): array
    {
        if (! $this->has($key)) {
            return [];
        }

        $schema = Filter::apply("blocks.$key.schema", $this->types[$key]['class']::schema(), $key);

        return array_values(array_filter($schema, fn (Component $component): bool => $component->isVisible()));
    }

    /**
     * The schema of the block type grouped under the block's `data`.
     */
    public function form(string $key): Group
    {
        return Group::make('data')->schema($this->schema($key));
    }

    /**
     * The visible components every block has, each setting the HTML attribute of its name on the block's root element,
     * which plugins may change through the `block.attributes.schema` filter.
     *
     * @return list<Component>
     */
    public function attributeSchema(): array
    {
        $schema = Filter::apply('block.attributes.schema', [
            Text::make('id')
                ->label(__('HTML ID'))
                ->description(__('A unique ID letting links point straight to the block, like #team.'))
                ->rules('regex:/^[A-Za-z][\w-]*$/', 'max:255'),
            Text::make('class')
                ->label(__('Additional CSS classes'))
                ->rules('regex:/^[\w\s-]*$/', 'max:255'),
        ]);

        return array_values(array_filter($schema, fn (Component $component): bool => $component->isVisible()));
    }

    /**
     * The schema every block has, grouped under the block's `attributes`.
     */
    public function attributesForm(): Group
    {
        return Group::make('attributes')->schema($this->attributeSchema());
    }

    /**
     * Make a block from its stored array, casting its data by the fields of its type,
     * or keeping it as it is when its type is not registered.
     *
     * @param  array{id?: string|null, type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}  $block
     */
    public function make(array $block): Block
    {
        $data = $block['data'] ?? [];
        $id = $block['id'] ?? null;
        $attributes = $this->attributesForm()->cast($block['attributes'] ?? []);

        if (! $this->has($block['type'])) {
            return new Missing($block['type'], $data, $id, $attributes);
        }

        return new ($this->types[$block['type']]['class'])($this->form($block['type'])->cast($data), $id, $attributes);
    }
}
