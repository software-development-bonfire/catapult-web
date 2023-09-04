<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Traits\HasPermission;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers, HasPermission;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()) {
            return redirect('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Authenticate user if success
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');


        if (Auth::attempt($credentials)) {
            if ($this->guard()->user()->status === Status::ACTIVE) {
                $request->session()->put('permissions', $this->guard()->user()->getPermissions());
    
                $link = $this->redirectUserTo();
                
                return response()->json(['redirectTo' => $link], 200); 
            } else {
                Auth::logout();
                throw ValidationException::withMessages([
                    'username' => Lang::get('validation.user_inactive')
                ]);
            }
        }
        throw ValidationException::withMessages([
            'username' => Lang::get('validation.the_provided_credentials_are_incorrect')
        ]);
    }

    public function redirectUserTo()
    {
        $permissions = Auth::user()->getPermissions();

        if (in_array($this->getPermissionCode('view.dashboard'), $permissions)) {
            $link = 'dashboard';
        } else if (in_array($this->getPermissionCode('view.user_account'), $permissions)) {
            $link = 'user-account';
        } else {
            $link = 'logs';
        }

        return $link;
    }

    /**
     * Logout authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->invalidate();

        if (Auth::check()) {
            $this->logout($request);
        }

        return redirect('/');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
