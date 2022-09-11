<?php

namespace Bekwoh\LaravelAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bekwoh\LaravelAction\LaravelAction
 */
class LaravelAction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bekwoh\LaravelAction\LaravelAction::class;
    }
}
