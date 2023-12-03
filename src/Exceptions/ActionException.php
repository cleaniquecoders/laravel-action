<?php

namespace CleaniqueCoders\LaravelAction\Exceptions;

use Exception;

class ActionException extends Exception
{
    public static function missingModelProperty($class)
    {
        return new self("Missing model property in class $class");
    }

    public static function emptyModelProperty($class)
    {
        return new self("Empty model property in class $class");
    }
}
