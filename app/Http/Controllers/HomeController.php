<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()) {
            $permissions = Auth::user()->getPermissions();
            if (in_array(110101, $permissions)) {
                return redirect('dashboard');
            } else if (in_array(110301, $permissions)) {
                return redirect('user-account');
            } else {
                return redirect('logs');
            }
        }
        return redirect('/');
    }
}
