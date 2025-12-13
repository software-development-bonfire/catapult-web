<?php

namespace App\Http\Controllers\KDS;

use App\Http\Controllers\Controller as Controller;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;

class KDSBaseController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    /**
     * Check if app_key from request is valid
     *
     * @param Request $request
     * @return boolean
     */
    public function isValidCatapultKey(Request $request)
    {
        $appKey = $request->get('app_key');
        $validAppKey = false;
        if ($appKey) {
            if ($appKey == $this->getCatapultKey()) {
                $validAppKey = true;
            }
        }
        return $validAppKey;
    }
}
