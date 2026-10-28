<?php

namespace App\Blocks;

use App\Facades\BlockType;
use Illuminate\Contracts\View\View;

/**
 * Holds the blocks edited in the component and the selected one, passing the registered block types to its view
 * as `$blockTypes`, ready for the `<x-block-editor.structure />`, `<x-block-editor.preview />` and
 * `<x-block-editor.settings />` components, each given `:$blocks :$blockTypes :$selectedBlock`.
 */
trait WithBlocks
{
    /**
     * @var list<array{id: string, type: string, data: array<string, mixed>, attributes: array<string, mixed>}>
     */
    public array $blocks = [];

    /**
     * The ID of the block whose settings are edited.
     */
    public ?string $selectedBlock = null;

    /**
     * Fill the editor with the given blocks.
     *
     * @param  iterable<Block>  $blocks
     */
    protected function fillBlocks(iterable $blocks): void
    {
        $this->blocks = collect($blocks)->map(fn (Block $block): array => $block->toArray())->values()->all();

        if ($this->selectedBlock && ! in_array($this->selectedBlock, array_column($this->blocks, 'id'), true)) {
            $this->selectedBlock = null;
        }
    }

    /**
     * Select the block to edit its settings, or none to leave the block settings.
     */
    public function selectBlock(?string $id): void
    {
        $this->selectedBlock = is_null($id) ? null : $this->blocks[$this->blockIndex($id)]['id'];
    }

    /**
     * Add a block of the given type at the given position, or after the last block, and select it.
     */
    public function addBlock(string $type, ?int $position = null): void
    {
        abort_unless(BlockType::has($type), 404);

        $this->insertBlock(BlockType::make(['type' => $type]), $position ?? count($this->blocks));
    }

    /**
     * Add a copy of the block right after it, and select the copy.
     */
    public function duplicateBlock(string $id): void
    {
        $index = $this->blockIndex($id);

        $this->insertBlock(BlockType::make([...$this->blocks[$index], 'id' => null]), $index + 1);
    }

    /**
     * Move the block to the given position, as `wire:sort` does when the block is dragged.
     */
    public function moveBlock(string $id, int $position): void
    {
        [$block] = array_splice($this->blocks, $this->blockIndex($id), 1);

        array_splice($this->blocks, max(0, $position), 0, [$block]);
    }

    public function removeBlock(string $id): void
    {
        array_splice($this->blocks, $this->blockIndex($id), 1);

        if ($this->selectedBlock === $id) {
            $this->selectedBlock = null;
        }
    }

    /**
     * The validation rules of the blocks and their data, keyed by their path.
     *
     * @return array<string, list<mixed>>
     */
    protected function blockRules(): array
    {
        return [
            'blocks' => ['array'],
            'blocks.*.id' => ['required', 'string', 'distinct'],
            'blocks.*.type' => ['required', 'string'],
            ...collect($this->blocks)
                ->flatMap(fn (array $block, int $index): array => [
                    ...BlockType::form($block['type'])->getRules("blocks.$index.data"),
                    ...BlockType::attributesForm()->getRules("blocks.$index.attributes"),
                ])
                ->all(),
        ];
    }

    /**
     * The validation attribute names of the blocks' fields, keyed by their path.
     *
     * @return array<string, string>
     */
    protected function blockValidationAttributes(): array
    {
        return collect($this->blocks)
            ->flatMap(fn (array $block, int $index): array => [
                ...BlockType::form($block['type'])->getValidationAttributes("blocks.$index.data"),
                ...BlockType::attributesForm()->getValidationAttributes("blocks.$index.attributes"),
            ])
            ->all();
    }

    /**
     * Select the first block with any of the given invalid fields, so its settings show their errors,
     * or none when only fields outside of the blocks are invalid.
     *
     * @param  list<string>  $invalid  The paths of the invalid fields.
     */
    protected function selectInvalidBlock(array $invalid): void
    {
        $indexes = collect($invalid)
            ->map(fn (string $path): ?int => preg_match('/^blocks\.(\d+)\./', $path, $matches) ? (int) $matches[1] : null)
            ->reject(fn (?int $index): bool => is_null($index));

        $selectedIndex = array_search($this->selectedBlock, array_column($this->blocks, 'id'), true);

        if (! $indexes->containsStrict($selectedIndex)) {
            $this->selectedBlock = $indexes->isEmpty() ? null : $this->blocks[$indexes->min()]['id'];
        }
    }

    /**
     * The blocks with their data cast by the fields of their type, without any value no field holds.
     */
    protected function castBlocks(): BlockCollection
    {
        return new BlockCollection(array_map(BlockType::make(...), $this->blocks));
    }

    public function renderingWithBlocks(View $view): void
    {
        $view->with('blockTypes', BlockType::list());
    }

    protected function insertBlock(Block $block, int $position): void
    {
        array_splice($this->blocks, max(0, $position), 0, [$block->toArray()]);

        $this->selectedBlock = $block->id;
    }

    protected function blockIndex(string $id): int
    {
        $index = array_search($id, array_column($this->blocks, 'id'), true);

        abort_if($index === false, 404);

        return $index;
    }
}
