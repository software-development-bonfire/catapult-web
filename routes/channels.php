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

Broadcast::channel('presence-channel-name', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
Broadcast::channel('private-my-channel', function ($user) {
    \Illuminate\Support\Facades\Log::info("User authenticated for private channel", ['user' => $user]);
    return true;
});