<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use BeyondCode\LaravelWebSockets\Apps\App;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Pusher\Pusher;
use Laravel\Passport\Passport;

class PusherAuthenticateController extends Controller
{
    use TokenResponsesJson, APIRequestTrait;

    public function authChannel(Request $request)
    {
        // Get the channel name from the request
        $channelName = $request->input('channel_name');

        /**
         * Find the app by using the header
         * and then reconstruct the PusherBroadcaster
         * using our own app selection.
         */
        $app = App::findById($request->header('x-app-id'));

        $broadcaster = new PusherBroadcaster(new Pusher(
            $app->key,
            $app->secret,
            $app->id,
            []
        ));

        /*
         * Since the dashboard itself is already secured by the
         * Authorize middleware, we can trust all channel
         * authentication requests in here.
         */
       return $broadcaster->validAuthenticationResponse($request, []);
    }
}
