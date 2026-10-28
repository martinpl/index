<?php

namespace App\Blocks;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Block>
 */
class BlockCollection extends Collection implements Htmlable
{
    /**
     * The HTML of the blocks, one after another, leaving out the blocks rendering nothing.
     */
    public function toHtml(): string
    {
        return $this->toBase()->map(fn (Block $block): string => $block->toHtml())->filter()->implode("\n");
    }
}
