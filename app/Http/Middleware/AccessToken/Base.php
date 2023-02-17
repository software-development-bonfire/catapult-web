<?php

namespace App\Http\Middleware\AccessToken;

use App\Traits\TokenResponsesJson;
use Closure;
use Laravel\Passport\Token;
use Lcobucci\JWT\Parser AS JWTParser;

class Base
{
    use TokenResponsesJson;

    protected $apiName;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jwtParser = new JWTParser();

        try {
            if (! $request->bearerToken()) {
                return $this->requiredTokenResponse();
            }

            $jwtToken = $jwtParser->parse($request->bearerToken());

            $scopes = $jwtToken->getClaim('scopes');

            if (! in_array($this->apiName, $scopes)) {
                return $this->invalidTokenResponse();
            }

            $token = Token::query()
                ->where('id', $jwtToken->getClaim('jti'))
                ->where('scopes', json_encode($scopes));

            if (! $token->exists()) {
                return $this->invalidTokenResponse();
            }

            if ($jwtToken->isExpired()) {
                return $this->expiredTokenResponse();
            }
        } catch (\Exception $ex) {
            return $this->malformedTokenResponse();
        }

        return $next($request);
    }
}
