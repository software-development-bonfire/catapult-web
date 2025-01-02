<?php

namespace App\Http\Controllers\KIOSK\v1;

use App\Events\PrivateMessageSent;
use App\Http\Controllers\KIOSK\KioskBaseController;
use Illuminate\Http\Request;

class EventTriggerController extends KioskBaseController
{
    public function trigger(Request $request)
    {
        $data = (object) stringToJson($request->all());

        broadcast(new PrivateMessageSent($data->message, $data->channel));

        return $this->successfulResponse($data, 'Private message sent!');
    }

}
