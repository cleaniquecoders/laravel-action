<?php

namespace CleaniqueCoders\LaravelAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\LaravelAction\LaravelAction
 */
class LaravelAction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CleaniqueCoders\LaravelAction\LaravelAction::class;
    }
}
