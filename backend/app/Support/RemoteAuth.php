<?php

namespace App\Support;

class RemoteAuth 
{
    public function user(): ?array
    {
        return request()->get('auth_user');
    }
}
