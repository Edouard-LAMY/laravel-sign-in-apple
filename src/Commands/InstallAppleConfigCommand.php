<?php

namespace Lamy\LaravelSignInApple\Commands;

use Illuminate\Console\Command;

class InstallAppleConfigCommand extends Command
{
    protected $signature = 'laravel-sign-in-apple:install';
    protected $description = 'Add Apple Sign-In configuration to config/services.php';

    public function handle()
    {
        $configPath = config_path('services.php');
        $configContents = file_get_contents($configPath);

        if (str_contains($configContents, "'apple'")) {
            $this->info("The 'apple' configuration already exists in services.php.");
            return;
        }

        $insertion = <<<PHP
            'apple' => [
                'team_id'       => env('APPLE_TEAM_ID'),
                'client_id'     => env('APPLE_CLIENT_ID'),
                'key_id'        => env('APPLE_KEY_ID'),
                'client_secret' => env('APPLE_CLIENT_SECRET'),
                'redirect'      => "/auth/callback/apple"
            ],
        PHP;

        // Inject the configuration before the closing array bracket
        $configContents = preg_replace('/\];\s*$/', $insertion . "\n];", $configContents);

        file_put_contents($configPath, $configContents);

        $this->info("✅ Apple configuration successfully added to config/services.php!");
    }
}
