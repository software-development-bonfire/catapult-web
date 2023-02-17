<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\SyncEntryRepository;
use App\Transformers\SyncEntryChosenTransformer;
use Illuminate\Http\Request;

class SyncEntryController extends Controller
{
    public function getChosen(Request $request)
    {
        $filters = (object) stringToJson($request->get('filters'));

        $syncEntries = app()->make(SyncEntryRepository::class)->list($filters);

        $syncEntries = fractal($syncEntries, SyncEntryChosenTransformer::class);

        return $syncEntries;
    }
}
