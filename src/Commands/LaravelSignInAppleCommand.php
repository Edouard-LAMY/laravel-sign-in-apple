<?php

namespace Lamy\LaravelSignInApple\Commands;

use Illuminate\Console\Command;

class LaravelSignInAppleCommand extends Command
{
    public $signature = 'laravel-sign-in-apple';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
