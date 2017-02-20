<?php

namespace Shanginn\Yalt\Facades;

use Illuminate\Support\Facades\Facade;

class Yalt extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'yalt';
    }
}
