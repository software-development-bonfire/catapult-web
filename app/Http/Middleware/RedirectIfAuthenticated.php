<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            $permissions = Auth::user()->getPermissions();
            
            if (in_array(110101, $permissions)) {
                return redirect('dashboard');
            } else if (in_array(110301, $permissions)) {
                return redirect('user-account');
            } else {
                return redirect('logs');
            }
        }

        return $next($request);
    }
}
