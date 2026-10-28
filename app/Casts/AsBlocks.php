<?php

namespace App\Casts;

use App\Blocks\Block;
use App\Blocks\BlockCollection;
use App\Blocks\Paragraph;
use App\Facades\BlockType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the attribute as a JSON list of blocks, reading it as a collection of blocks.
 *
 * @implements CastsAttributes<BlockCollection, iterable<Block|array{id?: string|null, type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|string|null>
 */
class AsBlocks implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): BlockCollection
    {
        return new BlockCollection(array_map(BlockType::make(...), blank($value) ? [] : json_decode($value, true)));
    }

    /**
     * Prepare the given value for storage, reading a string as a JSON list of blocks,
     * or as plain text, such as from the command line, making up a single paragraph.
     *
     * @param  iterable<Block|array{id?: string|null, type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|string|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = $this->isBlockList($value)
                ? $this->get($model, $key, $value, $attributes)
                : [['type' => Paragraph::$type, 'data' => ['text' => $value]]];
        }

        return collect($value)
            ->map(fn (Block|array $block): Block => $block instanceof Block ? $block : BlockType::make($block))
            ->values()
            ->toJson();
    }

    /**
     * Determine if the string is a JSON list of blocks, each having its type.
     */
    protected function isBlockList(string $value): bool
    {
        $blocks = json_decode($value, true);

        return is_array($blocks) && array_is_list($blocks)
            && collect($blocks)->every(fn (mixed $block): bool => is_array($block) && is_string($block['type'] ?? null));
    }
}
