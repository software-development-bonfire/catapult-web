<?php

namespace App\Http\Middleware;

use App\Traits\HasPermission;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    use HasPermission;
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
            if (in_array($this->getPermissionCode('view.dashboard'), $permissions)) {
                return redirect('dashboard');
            } else if (in_array($this->getPermissionCode('view.user_account'), $permissions)) {
                return redirect('user-account');
            } else {
                return redirect('logs');
            }
        }

        return $next($request);
    }
}
