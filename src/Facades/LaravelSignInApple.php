<?php

namespace Lamy\LaravelSignInApple\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lamy\LaravelSignInApple\Classes\LaravelSignInApple
 */
class LaravelSignInApple extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lamy\LaravelSignInApple\Classes\LaravelSignInApple::class;
    }
}
