<?php

namespace App\Console\Commands;

use App\EntryTypes\Page;
use App\Models\Option;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('page-role:list')]
#[Description('Lists page roles with their assigned pages')]
class PageRoleListCommand extends Command
{
    public function handle(): int
    {
        $pages = Option::get('page_roles', []);

        $this->table(['key', 'name', 'page'], collect(Page::roles())
            ->sortKeys()
            ->map(fn (string $name, string $key): array => [$key, $name, $pages[$key] ?? '']));

        return self::SUCCESS;
    }
}
