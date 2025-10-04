<?php 

namespace Lamy\LaravelSignInApple;

use Illuminate\Support\ServiceProvider;
use Lamy\LaravelSignInApple\Commands\InstallAppleConfigCommand;

class LaravelSignInAppleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAppleConfigCommand::class,
            ]);
        }
    }
}