<?php

namespace App\Admin\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\User;
use Illuminate\Http\JsonResponse;

class Auth extends Base
{
    public function login(): JsonResponse
    {
        return app(LoginController::class)->password($this->req, 'admin');
    }

    public function remember(): JsonResponse
    {
        return app(LoginController::class)->remember($this->req, 'admin');
    }

    public function logout(): void
    {
        /** @var User $user */
        $user = $this->req->user();
        $user->apiTokens()->delete();
    }

    public function user(): JsonResponse
    {
        return $this->jsonResponse($this->req->user() ?: true);
    }
}
