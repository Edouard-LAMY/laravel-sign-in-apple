<?php

namespace Lamy\LaravelSignInApple;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Lamy\LaravelSignInApple\Commands\LaravelSignInAppleCommand;

class LaravelSignInAppleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-sign-in-apple')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_sign_in_apple_table')
            ->hasCommand(LaravelSignInAppleCommand::class);
    }
}
