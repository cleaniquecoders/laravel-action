<?php

namespace CleaniqueCoders\LaravelAction;

use CleaniqueCoders\LaravelAction\Commands\LaravelActionCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelActionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-action')
            ->hasConfigFile()
            ->hasCommand(LaravelActionCommand::class);
    }
}
