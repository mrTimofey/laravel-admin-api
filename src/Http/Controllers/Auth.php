<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Auth\TokenGuard;
use Illuminate\Http\JsonResponse;
use MrTimofey\LaravelSimpleTokens\AuthenticatesUsers;

class Auth extends Base
{
    use AuthenticatesUsers;

    protected function guard(): TokenGuard
    {
        return auth($this->guard ?? config('admin_api.api_guard', 'api'));
    }
}
