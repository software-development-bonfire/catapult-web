<?php

namespace App\Http\Controllers\KDSMobile\v1;

use App\Http\Controllers\Controller;
use App\Enums\API\APIDefinedScopes;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;

class KDSMobileLoginController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    /**
     * Authenticate user for KDS Mobile Monitor.
     * POST /api/kds-mobile/v1/login
     */
    public function login(Request $request)
    {
        $repository = app()->make(CDISKitchenUserRepository::class);

        $result = $repository->authenticateByPasscode($request->get('passcode', ''));

        if ($result) {
            $token = $this->generateNewUserToken($result['user'], $request, false, false, array_keys(APIDefinedScopes::SCOPES));
            $response = [
                'session' => $token,
                'user' => $result['user'],
                'branch_bid' => $result['branch_bid'],
                'allowed_branches' => $result['allowed_branches'],
            ];
            return $this->tokenGeneratedResponse($response);
        }

        return $this->errorTokenResponse(null, __('auth.failed'));
    }

    /**
     * Logout by deleting access token.
     * POST /api/kds-mobile/v1/logout
     */
    public function logout(Request $request)
    {
        try {
            $tokenName = 'KDS-MOBILE-' . $request->getHost();
            Passport::token()->where('name', $tokenName)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 500);
        }
    }
}
