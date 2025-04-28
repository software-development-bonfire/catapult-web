<?php

namespace App\Http\Controllers\ECOM;

use App\Http\Controllers\Controller as Controller;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;

class EcomBaseController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    /**
     * Check if app_key from request is valid
     *
     * @param Request $request
     * @return boolean
     */
    public function isValidCDISKey(Request $request)
    {
        $appKey = $request->get('app_key');
        $validAppKey = false;
        if ($appKey) {
            if ($appKey == $this->getCDISKey()) {
                $validAppKey = true;
            }
        }
        return $validAppKey;
    }
}
