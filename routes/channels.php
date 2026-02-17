<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('item-availability', function ($message) {
    return $message;
});

Broadcast::channel('private-my-channel', function ($data) {
    \Illuminate\Support\Facades\Log::alert(json_encode($data));
    return $data;
});

Broadcast::channel('kds-device-{device}', function ($user, $device) {
    // Allow all authenticated users to listen to KDS device channels
    // In production, you might want to add more granular authorization
    return ['device' => $device];
});

Broadcast::channel('sirius-compute-engine', function ($data) {
    return $data;
});

Broadcast::channel('ots-request-status-channel', function ($data) {
    return $data;
});
