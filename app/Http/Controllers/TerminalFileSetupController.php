<?php

namespace App\Http\Controllers;

use App\Http\Requests\TerminalFileSetupRequest;
use App\Repositories\Contracts\TerminalFileSetupRepository;
use App\Services\TerminalFileSetupService;
use App\Transformers\TerminalFileSetupTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class TerminalFileSetupController extends Controller
{
    private $terminalFileSetupService;

    /**
     * import terminalFileSetupService.
     *
     * @param  FileStorageSetupService  $terminalFileSetupService
     *
     */
    public function __construct(TerminalFileSetupService $terminalFileSetupService)
    {
        $this->terminalFileSetupService = $terminalFileSetupService;
    }
    /**
     * Display a listing of the resource.
     *
     * @param  Request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $list = app()->make(TerminalFileSetupRepository::class)->list($request->all());

        $list = fractal($list, TerminalFileSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  TerminalFileSetupRequest  $request
     * @return JsonResponse
     */
    public function store(TerminalFileSetupRequest $request)
    {
       // try {
            $this->terminalFileSetupService->store($request->validated());
        //  catch(\Exception $ex) {
        //     return $this->errorResponse(
        //         [],
        //         __('error.terminal_file_setup_failed_create')
        //     );
        // }

        return $this->successfulResponse([], __('success.terminal_file_setup_created'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  TerminalFileSetupRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(TerminalFileSetupRequest $request, $id)
    {
        try {
            $this->terminalFileSetupService->update($request->validated(), $id);
        } catch(\Exception $ex) {
            return $this->errorResponse(
                [],
                __('error.terminal_file_setup_failed_update')
            );
        }

         return $this->successfulResponse([], __('success.terminal_file_setup_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return JsonResponse
     */
    public function destroy($bid)
    {
        try {
            $this->terminalFileSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('terminal_file_setup_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.terminal_file_setup_deleted')
        );
    }
}
