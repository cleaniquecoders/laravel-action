<?php

namespace Bekwoh\LaravelAction;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bekwoh\LaravelAction\Commands\LaravelActionCommand;

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
            ->hasCommand(LaravelActionCommand::class);
    }
}
