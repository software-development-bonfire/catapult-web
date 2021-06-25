<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAccountRequest;
use App\Repositories\Contracts\UserAccountRepository;
use App\Services\UserAccountService;
use App\Transformers\UserAccountTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class UserAccountController extends Controller
{
    /**
     * @param  UserAccountService  $userAccountService
     *
     */
    public function __construct(UserAccountService $userAccountService)
    {
        $this->middleware('has-permission:view.user_account')->only('view');
        $this->userAccountService = $userAccountService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(UserAccountRepository::class)->list($request->all());
        
        $list = fractal($list, UserAccountTransformer::class);

        return $this->successfulResponse($list);
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('user-account.list');
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
     * @param  UserAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserAccountRequest $request)
    {
        try {
            $this->userAccountService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.user_failed_create')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.user_created')
        );
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
     * @param  UserAccountRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(UserAccountRequest $request, $bid)
    {
        try {
            $this->userAccountService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.user_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.user_updated')
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy($bid)
    {
        try {
            $this->userAccountService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('user_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.user_deleted')
        );
    }
}
