<?php

namespace App\Blocks;

use App\Facades\Filter;
use App\Fields\Field;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class Block implements Arrayable, Htmlable, JsonSerializable
{
    /**
     * The block type key, required in block type classes.
     */
    public static string $type;

    public string $id;

    /**
     * @param  array<string, mixed>  $data  The values of the block's fields, keyed by field name.
     * @param  array<string, mixed>  $attributes  The HTML attributes of the block's root element, like its `id` and `class`.
     */
    public function __construct(public array $data = [], ?string $id = null, public array $attributes = [])
    {
        $this->id = $id ?? Str::lower((string) Str::ulid());
    }

    /**
     * The block type config when this class is registered as a block type.
     *
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return [];
    }

    /**
     * The fields holding the block's data, edited in the block editor.
     *
     * @return list<Field>
     */
    public static function fields(): array
    {
        return [];
    }

    public function type(): string
    {
        return static::$type;
    }

    /**
     * The HTML attributes of the block's root element, leaving out the empty ones.
     */
    public function htmlAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag(array_filter($this->attributes, filled(...)));
    }

    /**
     * Render the block, by default with the `blocks.{type}` view receiving its data, the block itself
     * and the `$attributes` of its root element.
     */
    public function render(): View|Htmlable|string
    {
        return view('blocks.'.static::$type, [...$this->data, 'block' => $this, 'attributes' => $this->htmlAttributes()]);
    }

    /**
     * The HTML of the block, which plugins may change through the `block.render` filter.
     */
    public function toHtml(): string
    {
        $html = $this->render();

        return Filter::apply('block.render', trim(match (true) {
            $html instanceof View => $html->render(),
            $html instanceof Htmlable => $html->toHtml(),
            default => $html,
        }), $this);
    }

    /**
     * @return array{id: string, type: string, data: array<string, mixed>, attributes: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type(),
            'data' => $this->data,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * @return array{id: string, type: string, data: array<string, mixed>, attributes: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
