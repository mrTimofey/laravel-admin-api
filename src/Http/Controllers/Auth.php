<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\JsonResponse;
use MrTimofey\LaravelSimpleTokens\AuthenticatesUsers;

class Auth extends Base
{
    use AuthenticatesUsers;
}
