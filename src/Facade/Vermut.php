<?php

namespace Kavinsky\Vermut\Facade;

use Illuminate\Support\Facades\Facade;

class Vermut extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'vermut';
    }
}
