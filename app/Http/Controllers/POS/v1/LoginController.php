<?php

namespace App\Http\Controllers\POS\v1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserAccountRepository;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class LoginController extends Controller
{
    use TokenResponsesJson;

    /**
     * Authenticate user and provide access token for POS API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = app()->make(UserAccountRepository::class)->where([
            'username' => $credentials['username']
        ])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            if ($user->status === Status::ACTIVE) {

                $tokenName = 'POS-'.$request->getHost();

                Passport::token()->where('name', $tokenName)->delete();

                $token = $user->createToken($tokenName, ['pos']);

                return $this->tokenGeneratedResponse($token);
            }
        }

        return $this->errorResponse([], __('auth.failed'));
    }

    /**
     * Logout by deleting access token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $tokenName = 'POS-'.$request->getHost();

            Passport::token()->where('name', $tokenName)->delete();

            return $this->successfulResponse();
        } catch (\Exception $ex) {
            return $this->errorResponse();
        }
    }
}
