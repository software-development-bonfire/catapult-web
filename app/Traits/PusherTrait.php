<?php

namespace App\Traits;

use Pusher\Pusher;

trait PusherTrait
{
    public $pusher;

    private function initializePusher()
    {
        $pusherAppId = config()->get('broadcasting.connections.cdis_pusher.app_id');
        $pusherAppKey = config()->get('broadcasting.connections.cdis_pusher.key');
        $pusherAppSecret = config()->get('broadcasting.connections.cdis_pusher.secret');
        $pusherAppCluster = config()->get('broadcasting.connections.cdis_pusher.options.cluster');

        $options = [
            'cluster' => $pusherAppCluster,
            'useTLS' => true
        ];

        $this->pusher = new Pusher($pusherAppKey, $pusherAppSecret, $pusherAppId, $options);
    }
}
