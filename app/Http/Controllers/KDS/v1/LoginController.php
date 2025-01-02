<?php

namespace App\Http\Controllers\KDS\v1;

use App\Enums\API\APIDefinedScopes;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class LoginController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

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
                $token = $this->generateNewUserToken($user, $request, false, false, array_keys(APIDefinedScopes::SCOPES));
                $response = [
                    'session' => $token,
                    'user' => $user,
                    'auth_type' => 'user',
                ];
                return $this->tokenGeneratedResponse($response);
            }
        }

        return $this->errorTokenResponse(null, __('auth.failed'));
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
            $tokenName = 'KDS-'. $request->getHost();

            Passport::token()->where('name', $tokenName)->delete();

            return $this->successfulResponse();
        } catch (\Exception $ex) {
            return $this->errorResponse();
        }
    }
}
