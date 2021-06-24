<?php

namespace App\Http\Middleware;

use App\Enums\Permissions;
use App\Traits\HasPermission;
use App\Traits\ResponsesJson;
use Closure;

class CheckPermission
{
    use HasPermission, ResponsesJson;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions)
    {
        if (! $this->hasPermissionTo($permissions)) {
            if ($request->wantsJson()) {
                return $this->notAuthorizedResponse(__('message.unauthorized'));
            } else {
                abort(404);
            }
        }

        return $next($request);
    }
}
