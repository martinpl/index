<?php

namespace App\Console\Commands;

use App\Actions\ScopePackage;
use App\Facades\Theme;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('theme:scope {theme}')]
#[Description('Prefixes the theme dependencies with its namespace')]
class ThemeScopeCommand extends Command
{
    public function handle(ScopePackage $scopePackage): int
    {
        $name = $this->argument('theme');
        if (! Theme::isInstalled($name)) {
            $this->error("Theme [$name] does not exist.");

            return self::FAILURE;
        }

        try {
            $scopePackage(Theme::path($name));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Scoped theme [$name].");

        return self::SUCCESS;
    }
}
