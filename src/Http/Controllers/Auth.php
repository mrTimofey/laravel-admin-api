<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\JsonResponse;

class Auth extends Base
{
    protected function userResponse()
    {
        return ['name' => 'Admin', 'api_token' => 'token', 'remember_token' => 'token'];
    }

    public function user()
    {
        return $this->userResponse();
    }

    public function login()
    {
        return $this->userResponse();
    }

    public function remember()
    {
        return $this->userResponse();
    }

    public function logout() {}
}
