<?php

namespace App\Http\Controllers\KDSMobile\v1;

use App\Http\Controllers\Controller;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use BeyondCode\LaravelWebSockets\Apps\App;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Pusher\Pusher;

class KDSMobilePusherAuthController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    /**
     * Authenticate a channel subscription for KDS Mobile Monitor.
     * POST /api/kds-mobile/v1/broadcasting/auth
     */
    public function authChannel(Request $request)
    {
        $app = App::findByKey($request->header('x-app-key'));

        $broadcaster = new PusherBroadcaster(new Pusher(
            $app->key,
            $app->secret,
            $app->id,
            ['debug' => true]
        ));

        return $broadcaster->validAuthenticationResponse($request, []);
    }
}
