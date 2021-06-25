<?php

namespace App\Http\Controllers;

use App\Enums\Permissions;
use App\Traits\HasPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use HasPermission;
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()) {
            $permissions = Auth::user()->getPermissions();
            if (in_array($this->getPermissionCode('view.dashboard'), $permissions)) {
                return redirect('dashboard');
            } else if (in_array($this->getPermissionCode('view.user_account'), $permissions)) {
                return redirect('user-account');
            } else {
                return redirect('logs');
            }
        }
        return redirect('/');
    }
}
