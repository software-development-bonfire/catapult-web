<?php

namespace App\Events\KDS;

/**
 * KDS Command Event
 * 
 * Broadcast device commands (shutdown, restart, config, etc.)
 * Channel: kds-command-{deviceUid}
 * 
 * Note: Device identifier is in the channel name, not in the payload.
 */
class KDSCommandEvent extends KDSEventBase
{
    public $command;
    public $data;

    public function __construct(string $deviceUid, string $command, $data = null)
    {
        $this->deviceUid = $deviceUid;
        $this->command = $command;
        $this->data = $data;
    }

    protected function getChannelName(): string
    {
        return 'kds-command-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'command-event';
    }

    public function broadcastWith()
    {
        return [
            'command' => $this->command,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}
