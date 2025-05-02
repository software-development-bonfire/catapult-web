<?php

namespace App\Http\Middleware;
use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin' , '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET');
        $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, ApplicationID, Authorization, x-csrf-token, x-app-key');

        return $response;
    }
}
