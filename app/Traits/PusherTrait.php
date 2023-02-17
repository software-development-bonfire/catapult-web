<?php

namespace App\Traits;

use Pusher\Pusher;

trait PusherTrait
{
    public $pusher;

    private function initializePusher()
    {
        $pusherAppId = config()->get('broadcasting.connections.pusher.app_id');
        $pusherAppKey = config()->get('broadcasting.connections.pusher.key');
        $pusherAppSecret = config()->get('broadcasting.connections.pusher.secret');
        $pusherAppCluster = config()->get('broadcasting.connections.pusher.options.cluster');

        $options = [
            'cluster' => $pusherAppCluster,
            'useTLS' => true
        ];

        $this->pusher = new Pusher($pusherAppKey, $pusherAppSecret, $pusherAppId, $options);
    }
}
