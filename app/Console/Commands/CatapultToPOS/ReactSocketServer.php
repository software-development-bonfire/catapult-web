<?php

namespace App\Console\Commands\CatapultToPOS;

use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use React\Socket\LimitingServer;
use React\Socket\SocketServer;

class ReactSocketServer extends Command
{
    use GenericHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:react-server-listen {--interval=true}{--limit=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen events occurring in Catapult socket server';

    protected $socketId = null;
    protected $resolvedCount = 0;
    protected $defaultLimit = 9999999;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
        while (true) {

            $this->startServer();

            sleep(5);
        }
        */
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function startServer()
    {

        $socket = new SocketServer('[::1]:6001', array(
            'tls' => array(
                'local_cert' => (__DIR__ . '/localhost.pem')
            )
        ));

        $socket = new LimitingServer($socket, null);

        $socket->on('connection', function (\React\Socket\ConnectionInterface $connection) use ($socket) {
            $this->createLog('[' . $connection->getRemoteAddress() . ' connected]' . PHP_EOL, 'info', true, ['CONNECTION INIT']);

            // whenever a new message comes in
            $connection->on('data', function ($data) use ($connection, $socket) {
                // remove any non-word characters (just for the demo)
                $data = trim(preg_replace('/[^\w\d \.\,\-\!\?]/u', '', $data));

                // ignore empty messages
                if ($data === '') {
                    return;
                }

                // prefix with client IP and broadcast to all connected clients
                $data = trim(parse_url($connection->getRemoteAddress(), PHP_URL_HOST), '[]') . ': ' . $data . PHP_EOL;
                foreach ($socket->getConnections() as $connection) {
                    $connection->write($data);
                }
            });

            $connection->on('close', function () use ($connection) {
                $this->createLog('[' . $connection->getRemoteAddress() . ' disconnected]', 'error', true, ['CONNECTION DISCONNECTED']);
            });
        });

        $socket->on('error', function (\Exception $e) {
            $this->createLog($e->getMessage() . PHP_EOL, 'error', true, ['CONNECTION FAILED']);
        });

        $this->createLog('Listening on ' . $socket->getAddress() . PHP_EOL, 'info', true, ['CONNECTION INIT']);
    }
}
