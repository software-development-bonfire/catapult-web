<?php

namespace App\Http\Controllers\KDS\v1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class LoginController extends Controller
{
    use TokenResponsesJson;

    /**
     * Authenticate user and provide access token for KDS API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = app()->make(CDISKitchenUserRepository::class)->where([
            'username' => $credentials['username']
        ])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            if ($user->status === Status::ACTIVE) {

                $tokenName = 'KDS-'. $request->getHost();

                Passport::token()->where('name', $tokenName)->delete();

                $token = $user->createToken($tokenName, ['kds']);

                return $this->tokenGeneratedResponse($token);
            }
        }

        return $this->errorResponse([], __('auth.failed'));
    }
}
