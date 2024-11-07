<?php

namespace App\Http\Controllers\POS\v1;

use App\Events\PrivateMessageSent;
use App\Http\Controllers\POS\POSBaseController;
use Illuminate\Http\Request;

class EventTriggerController extends POSBaseController
{
    public function trigger(Request $request)
    {
        $data = (object) stringToJson($request->all());

        broadcast(new PrivateMessageSent($data->message, $data->channel));

        return $this->successfulResponse($data, 'Private message sent!');
    }

}
