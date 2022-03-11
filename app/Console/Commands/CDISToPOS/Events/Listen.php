<?php

namespace App\Console\Commands\CDISToPOS\Events;

use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;

class Listen extends Command
{
    use GenericHelper, PusherTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:event-listen {--interval=true}{--limit=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen events occurring in CDIS';

    protected $socketId = null;

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
        while (true) {
            $this->connect();
            sleep(5);
        }
    }

    public function connect()
    {
        $pusherAppKey = config('broadcasting.connections.pusher.key');
        $clientId = config('configuration.client_id');
        $branchCode = config('configuration.branch_code');
        $loop = Loop::get();

        \Ratchet\Client\connect('wss://ws-eu.pusher.com/app/'.$pusherAppKey.'?protocol=7&client=js&version=7.0.6&flash=false')
            ->then(function($connection) use($loop, &$socketConnection, $clientId, $branchCode) {
                $connection->send('{"event":"pusher:subscribe","data":{"auth":"","channel":"'.$this->cdisAndCatapultSyncChannel($clientId, $branchCode).'"}}');

                $pingTimer = $this->getPingTimer($loop, $connection);

                $connection->on('message', function(MessageInterface $message) use ($loop, $connection, &$pingTimer, $clientId, $branchCode) {
                    $loop->cancelTimer($pingTimer);
                    $pingTimer = $this->getPingTimer($loop, $connection);
                    $this->eventListener($message, $connection, $clientId, $branchCode);
                });

                $connection->on('close', function($code = null, $reason = null) use($loop, &$pingTimer) {
                    $this->createLog($reason, 'warn', true, ['CONNECTION CLOSED'], [$code]);
                    $loop->cancelTimer($pingTimer);
                    $loop->stop();

                    gc_collect_cycles();
                });
            }, function ($e) use ($loop) {
                $this->error("Could not connect: {$e->getMessage()}");
                $loop->stop();
                throw new \Exception();
            });

        $loop->run();

        return true;
    }

    private function getPingTimer($loop, $connection)
    {
        return $loop->addPeriodicTimer(120, function() use($loop, $connection) {
            $connection->send('{"event":"pusher:ping","data":{}}');
        });
    }

    public function eventListener($message, $connection, $clientId, $branchCode)
    {
        $payload = json_decode($message);

        if (isset($payload->event)) {
            switch ($payload->event) {
                case "pusher:connection_established":
                    $this->createLog($payload->data, 'info', true, ['CONNECTION ESTABLISHED']);
                    $data = json_decode($payload->data);
                    $this->socketId = $data->socket_id;
                    $this->initializePusher();
                    break;
                case "pusher_internal:subscription_succeeded":
                    $this->createLog($payload->channel, 'info', true, ['CHANNEL']);
                    $this->createLog('Listening to events...', 'info', true, ['LOG']);
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($clientId, $branchCode), 'PongCatapult', '{}', $this->socketId, true);
                    break;

                case "pusher:error":
                    $this->createLog($payload->data->message, 'warn', true, ['PUSHER', 'ERROR'], [$payload->data->code]);
                    break;

                case "App\Events\Catapult\Ping":
                    $this->createLog(json_encode($payload), 'info', true, ['EVENT', $payload->event]);
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($clientId, $branchCode), 'PongCatapult', '{}', $this->socketId, true);
                    $this->createLog(json_encode($payload), 'warn', true, ['EVENT', 'PongCatapult']);
                    break;

                case "App\Events\Catapult\TriggerCDISFetchDataForSync":
                    $this->createLog(json_encode($payload), 'info', true, ['EVENTS', $payload->event]);
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($clientId, $branchCode), 'Syncing', '{}', $this->socketId, true);
                    Artisan::queue('cdis:fetch-data-for-sync', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true']);
                    break;

                case "App\Events\Catapult\TriggerCDISDataConversion":
                    $this->createLog(json_encode($payload), 'info', true, ['EVENTS', $payload->event]);
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($clientId, $branchCode), 'Converting', '{}', $this->socketId, true);
                    Artisan::queue('cdis:convert-data-to-file', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true']);
                    break;

                default:
                    $this->createLog(json_encode($payload->data), 'info', true, ['EVENT', $payload->event]);
            }
        }
    }
}
