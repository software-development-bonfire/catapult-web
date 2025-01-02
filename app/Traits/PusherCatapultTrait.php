<?php

namespace App\Traits;

use Pusher\Pusher;

trait PusherCatapultTrait
{
    public $pusher;

    private function initializeCatapultPusher()
    {
        $pusherAppId = config()->get('broadcasting.connections.pusher.app_id');
        $pusherAppKey = config()->get('broadcasting.connections.pusher.key');
        $pusherAppSecret = config()->get('broadcasting.connections.pusher.secret');
        $pusherAppCluster = config()->get('broadcasting.connections.pusher.options.cluster');

        $options = [
            'cluster' => $pusherAppCluster,
            'useTLS' => false
        ];

        $this->pusher = new Pusher($pusherAppKey, $pusherAppSecret, $pusherAppId, $options);
    }
}
