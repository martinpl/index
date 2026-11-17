<?php

namespace App\Actions;

use App\Entries\Page;
use App\Enums\EntryStatus;
use App\Facades\Plugin;
use App\Facades\Role;
use App\Facades\Theme;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;

class InstallSite
{
    /**
     * Set up the site with its name, default roles, verified administrator, home page, theme and plugins.
     *
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function __invoke(Site $site, string $name, array $admin): User
    {
        return $site->execute(function (Site $site) use ($name, $admin): User {
            Setting::set('site_name', $name, 'core');

            $this->createDefaultRoles($site);
            $user = $this->createAdmin($admin);
            $this->createHomePage($user);

            Theme::activate('howdy');
            Plugin::activate('blog');

            return $user;
        });
    }

    /**
     * Create the default roles, granting the administrator every core ability, along with managing
     * the sites on the main site, and making the member, who has none, the role of new users.
     */
    protected function createDefaultRoles(Site $site): void
    {
        foreach (['administrator', 'member'] as $role) {
            Role::create($role);
        }

        Role::grant('administrator', [
            'entries.manage',
            'entries.media.manage',
            'users.manage',
            'themes.manage',
            'plugins.manage',
            'settings.manage',
            ...$site->isMain() ? ['sites.manage'] : [],
        ]);

        Setting::set('default_role', 'member', 'core');
    }

    /**
     * Create the verified administrator account.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    protected function createAdmin(array $attributes): User
    {
        $user = User::create($attributes);
        $user->markEmailAsVerified();
        $user->giveRole('administrator');

        return $user;
    }

    /**
     * Create a published page and assign it to the home page role.
     */
    protected function createHomePage(User $author): void
    {
        $page = Page::create([
            'status' => EntryStatus::Publish,
            'slug' => 'home',
            'name' => __('Home'),
            'user_id' => $author->id,
            'owner' => 'core',
        ]);

        Setting::set('page_roles', ['home' => $page->id], 'core');
    }
}
