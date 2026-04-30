<?php

namespace App\Http\Controllers\KDS\v1;

use App\Enums\API\APIDefinedScopes;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;

class LoginController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    private $authenticatedPasscodeOnly = true;
    /**
     * Authenticate user and provide access token for KDS API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $repository = app()->make(CDISKitchenUserRepository::class);

        // Due to some changes on CDIS, passcode only validation is implemented
        if ($this->authenticatedPasscodeOnly) {
            $result = $repository->authenticateByPasscode($request->get('passcode', ''));
            $authType = 'passcode';
        } else {
            $credentials = $request->only('username', 'password');
            $result = $repository->authenticateByCredentials($credentials['username'] ?? '', $credentials['password'] ?? '');
            $authType = 'user';
        }

        if ($result) {
            $token = $this->generateNewUserToken($result['user'], $request, false, false, array_keys(APIDefinedScopes::SCOPES));
            $response = [
                'session' => $token,
                'user' => $result['user'],
                'auth_type' => $authType,
                'branch_bid' => $result['branch_bid'],
                'allowed_branches' => $result['allowed_branches'],
            ];
            return $this->tokenGeneratedResponse($response);
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
