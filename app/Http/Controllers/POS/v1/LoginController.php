<?php

namespace App\Http\Controllers\POS\v1;

use App\Enums\API\APIDefinedScopes;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\POS\POSBaseController;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\UserAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class LoginController extends POSBaseController
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
        $data = (object) stringToJson($request->all());
        if (! empty($data->auth_type)) {
            $this->authType = $data->auth_type;
        }

        if ($this->authType == 'device') {
            $devices = app()->make(DeviceSettingsRepository::class)->where([
                'device_uid' => $data->device_uid,
                'device_type' => $data->device_type,
            ])->first();
            if ($devices) {
                // If device exist, then we must use a superadmin info to generate a user tokens
                $user = app()->make(UserAccountRepository::class)->where([
                    'type' => UserType::SUPERADMIN
                ])->first();
                $token = $this->generateNewUserToken($user, $request, false, $user->isSuperAdmin(), array_keys(APIDefinedScopes::SCOPES));
                $response = [
                    'session' => $token,
                    'user' => $user,
                    'auth_type' => $this->authType,
                    'device' => $devices,
                ];
                return $this->tokenGeneratedResponse($response);
            }
        } else  if ($this->authType == 'user') {

            $credentials = $request->only('username', 'password');

            $user = app()->make(UserAccountRepository::class)->where([
                'username' => $credentials['username']
            ])->first();

            if ($user && Hash::check($credentials['password'], $user->password)) {
                $isSuperadmin = $user->isSuperAdmin();
                if ($user->status === Status::ACTIVE) {
                    $token = $this->generateNewUserToken($user, $request, false, $isSuperadmin, array_keys(APIDefinedScopes::SCOPES));
                    $response = [
                        'session' => $token,
                        'user' => $user,
                        'auth_type' => $this->authType,
                    ];
                    return $this->tokenGeneratedResponse($response);
                }
            }
        } else {
            return $this->errorTokenResponse([], __('auth.unknown_auth_type'));
        }

        return $this->errorTokenResponse([], __('auth.failed'));
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
