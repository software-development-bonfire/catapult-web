<?php

namespace App\Http\Controllers\SCE\v1;

use App\Enums\API\APIDefinedScopes;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\SCE\SCEBaseController;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\UserAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class LoginController extends SCEBaseController
{
    private $appName;
    private $authType;

    public function __construct()
    {
        $this->appName = config('app.name');
        $this->authType = config('system.api.auth_type');
    }

    /**
     * Authenticate user and provide access token for POS API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $catapultAppKey = config()->get('system.catapult_app_key');
        $appKey = $request->get('app_key');

        if ($appKey == $catapultAppKey) {
            $user = app()->make(UserAccountRepository::class)->where([
                'type' => UserType::SUPERADMIN
            ])->first();
            $token = $this->generateNewUserToken($user, $request, false, $user->isSuperAdmin(), array_keys(APIDefinedScopes::SCOPES));
            $response = [
                'session' => $token,
                'user' => $user,
                'auth_type' => $this->authType,
            ];
            return $this->tokenGeneratedResponse($response);
        } else {
            return $this->errorTokenResponse(null, __('error.missing_app_key_or_invalid'), []);
        }
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
            $tokenName = $this->appName.'-'.$request->getHost();

            Passport::token()->where('name', $tokenName)->delete();

            return $this->successfulResponse();
        } catch (\Exception $ex) {
            return $this->errorResponse();
        }
    }
}
