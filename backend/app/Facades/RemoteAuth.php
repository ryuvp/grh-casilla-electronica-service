<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class RemoteAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'remoteauth';
    }
}
