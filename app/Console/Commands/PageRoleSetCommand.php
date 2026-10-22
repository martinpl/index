<?php

namespace App\Console\Commands;

use App\EntryTypes\Page;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('page-role:set {role} {page? : The page ID, omit to unassign the role}')]
#[Description('Assigns a page to a role')]
class PageRoleSetCommand extends Command
{
    public function handle(): int
    {
        $role = $this->argument('role');
        if (! array_key_exists($role, Page::roles())) {
            $this->error("Page role [$role] does not exist.");

            return self::FAILURE;
        }

        $pages = Setting::get('page_roles', []);

        $pageId = $this->argument('page');
        if (! $pageId) {
            unset($pages[$role]);
            Setting::set('page_roles', $pages, 'core');
            $this->info("Unassigned page role [$role].");

            return self::SUCCESS;
        }

        $page = Page::withAnyStatus()->find($pageId);
        if (! $page) {
            $this->error("Page [$pageId] does not exist.");

            return self::FAILURE;
        }

        $pages[$role] = $page->id;
        Setting::set('page_roles', $pages, 'core');
        $this->info("Assigned page [$page->id] to role [$role].");

        return self::SUCCESS;
    }
}
