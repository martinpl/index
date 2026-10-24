<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\EntryTypes\Page;
use App\Enums\EntryStatus;
use App\Facades\Plugin;
use App\Facades\Role;
use App\Facades\Theme;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('index:install
    {--name= : The name of the site}
    {--domain= : The domain of the site}
    {--admin-name= : The name of the administrator}
    {--admin-email= : The email address of the administrator}
    {--admin-password= : The password of the administrator, generated when omitted}')]
#[Description('Runs installation process')]
class InstallCommand extends Command
{
    use ProfileValidationRules;

    public function handle(): int
    {
        $exitCode = $this->call('migrate');
        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        if (Site::exists()) {
            $this->info('Site already installed.');

            return self::SUCCESS;
        }

        $name = $this->option('name');
        if (! $name) {
            $name = $this->ask('Site name');
        }

        $domain = $this->option('domain');
        if (! $domain) {
            $domain = $this->ask('Site domain', 'localhost');
        }

        $adminName = $this->option('admin-name');
        if (! $adminName) {
            $adminName = $this->ask('Admin name');
        }

        $adminEmail = $this->option('admin-email');
        if (! $adminEmail) {
            $adminEmail = $this->ask('Admin email');
        }

        $adminPassword = $this->option('admin-password') ?? Str::password();

        $validator = Validator::make([
            'name' => $name,
            'domain' => $domain,
            'admin_name' => $adminName,
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
        ], [
            'name' => ['required'],
            'domain' => ['required', 'string', 'max:255'],
            'admin_name' => $this->nameRules(),
            'admin_email' => $this->emailRules(),
            'admin_password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Site::create([
            'domain' => $domain,
        ]);
        Setting::set('site_name', $name, 'core');
        $this->createDefaultRoles();
        $admin = $this->createAdmin($adminName, $adminEmail, $adminPassword);
        $this->createHomePage($admin);
        Theme::activate('index');
        Plugin::activate('blog');
        $this->info('Site installed successfully.');

        if ($this->option('admin-password') === null) {
            $this->line("Admin password: $adminPassword");
        }

        return self::SUCCESS;
    }

    /**
     * Create the default roles, granting the administrator every core ability.
     */
    protected function createDefaultRoles(): void
    {
        foreach (['administrator'] as $role) {
            Role::create($role);
        }

        Role::grant('administrator', ['entries.manage', 'plugins.manage', 'settings.manage']);
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

    /**
     * Create the verified administrator account.
     */
    protected function createAdmin(string $name, string $email, string $password): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->markEmailAsVerified();
        $user->giveRole('administrator');

        return $user;
    }
}
