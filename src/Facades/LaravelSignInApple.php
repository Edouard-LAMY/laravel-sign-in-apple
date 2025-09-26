<?php

namespace Lamy\LaravelSignInApple\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lamy\LaravelSignInApple\LaravelSignInApple
 */
class LaravelSignInApple extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lamy\LaravelSignInApple\LaravelSignInApple::class;
    }
}
