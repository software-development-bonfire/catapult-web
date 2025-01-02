<?php

namespace App\Http\Middleware\CDIS\API;

use App\Enums\API\APIDefinedScopes;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Closure;

class CheckCDISAppKey
{
    use TokenResponsesJson, APIRequestTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $catapultKey = config()->get('system.catapult_key');
        $appKey = $request->get('app_key');
        if ($appKey != $catapultKey /* $this->getCatapultKey()*/) {
            return $this->errorTokenResponse(null, __('error.missing_app_key_or_invalid'), []);
        }

        $appId = $request->get('app_id');
        $definedScopes = config()->get('system.api.scopes');
        //if (! in_array($appId, array_keys(APIDefinedScopes::SCOPES))) {
        if (! in_array($appId, array_keys($definedScopes))) {
            return $this->errorTokenResponse(null, __('error.invalid_app_id'), []);
        }

        return $next($request);
    }
}
