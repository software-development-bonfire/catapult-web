<?php

namespace App\Traits;

use App\Enums\SessionScopeSourceType;
use Illuminate\Support\Carbon;

trait APIRequestTrait
{
    /**
     * Get trimmed app key
     *
     * @param bool $removeNonAlphaNumeric
     * @return string $appKey
     */
    public function trimAppKey($removeNonAlphaNumeric = true)
    {
        $appKey = config()->get('app.key');
        $appKey = substr($appKey, 7);
        if ($removeNonAlphaNumeric) {
            $appKey = preg_replace('/[^a-z0-9]/i', '', $appKey);
        }
        return $appKey;
    }

    /**
     * Delete all user API tokens (to logout all connected devices) 
     * then generate new token
     *
     * @param mixed $user
     * @param Request $request
     * @param bool $autoLogoutOtherDevice
     * @param bool $isSuperAdmin
     * @return mixed $userToken
     */
    public function generateNewUserToken($user, $request, $autoLogoutOtherDevice, $isSuperAdmin = false, $scopes = [])
    {
        if ($autoLogoutOtherDevice && ! $isSuperAdmin) {
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
        }
        $appName = config('app.name');

        $tokenName = "{$appName}-{$request->getHost()}";
        \Illuminate\Support\Facades\Log::alert(json_encode($scopes));
        return $user->createToken($tokenName, $scopes);
    }

    /**
     * Get Catapult key
     *
     * @return string
     */
    public function getCatapultKey()
    {
        return config()->get('system.catapult_key');
    }
}
