<?php

/*
namespace App\Console\Commands\CatapultToPOS;

use App\Traits\GenericHelper;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    use GenericHelper;

    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        $this->createLog("New connection! ({$conn->resourceId})", 'info', true, ['CONNECTION ESTABLISHED']);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        $this->createLog("Connection {$conn->resourceId} has disconnected", 'info', true, ['CONNECTION DICONNECTED']);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        trigger_error("An error has occurred: {$e->getMessage()}\n", E_USER_WARNING);

        $conn->close();
    }
}
*/