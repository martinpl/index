<?php

namespace App\Foundation;

use App\Blocks\Block;
use App\Blocks\Missing;
use App\Facades\Filter;
use App\Fields\Field;
use App\Fields\Group;
use App\Fields\Text;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BlockType
{
    /**
     * @var array<string, array{key: string, name: string, icon: string, description: string|null, class: class-string<Block>, owner: string}>
     */
    protected array $types = [];

    /**
     * @param  class-string<Block>  $class
     * @param  array{name?: string, icon?: string, description?: string}  $config
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
            'class' => $class,
            'owner' => $owner,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->types);
    }

    /**
     * @return array{key: string, name: string, icon: string, description: string|null, class: class-string<Block>, owner: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->types[$key] ?? null;
    }

    /**
     * The block types in the order they were registered, which is the order the block editor offers them in.
     *
     * @return Collection<string, array{key: string, name: string, icon: string, description: string|null, class: class-string<Block>, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->types);
    }

    /**
     * The visible fields of the block type, which plugins may change through the `blocks.{key}.fields` filter.
     *
     * @return list<Field>
     */
    public function fields(string $key): array
    {
        if (! $this->has($key)) {
            return [];
        }

        $fields = Filter::apply("blocks.$key.fields", $this->types[$key]['class']::fields(), $key);

        return array_values(array_filter($fields, fn (Field $field): bool => $field->isVisible()));
    }

    /**
     * The fields of the block type grouped under the block's `data`.
     */
    public function form(string $key): Group
    {
        return Group::make('data')->fields($this->fields($key));
    }

    /**
     * The visible fields every block has, each setting the HTML attribute of its name on the block's root element,
     * which plugins may change through the `block.attributes.fields` filter.
     *
     * @return list<Field>
     */
    public function attributeFields(): array
    {
        $fields = Filter::apply('block.attributes.fields', [
            Text::make('id')
                ->label(__('HTML ID'))
                ->description(__('A unique ID letting links point straight to the block, like #team.'))
                ->rules('regex:/^[A-Za-z][\w-]*$/', 'max:255'),
            Text::make('class')
                ->label(__('Additional CSS classes'))
                ->rules('regex:/^[\w\s-]*$/', 'max:255'),
        ]);

        return array_values(array_filter($fields, fn (Field $field): bool => $field->isVisible()));
    }

    /**
     * The fields every block has, grouped under the block's `attributes`.
     */
    public function attributesForm(): Group
    {
        return Group::make('attributes')->fields($this->attributeFields());
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
