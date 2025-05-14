<?php

namespace App\Console\Commands\EcomToPOS\Events;

use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use App\Http\Controllers\ECOM\v1\TerminalTransactionController;

class EcomListen extends Command
{
    use GenericHelper, PusherTrait;

    protected $signature = 'ecom:event-listen {--interval=true}{--limit=true}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $pusherDomain = 'ws-eu.pusher.com';

        while (true) {
            if (
                $this->hasInternetConnection() &&
                $this->hasInternetConnection($pusherDomain)
            ) {
                $this->connect();
            } else {
                $this->createLog("Could not connect: No internet connection.", 'warn', true, ['CONNECTION ERROR']);
            }

            sleep(5);
        }
    }

    public function connect()
    {
        $pusherAppKey = config('broadcasting.connections.cdis_pusher.key');
        $pusherCluster = config('broadcasting.connections.cdis_pusher.options.cluster');
        $clientId = config('configuration.client_id');
        $branchCode = config('configuration.branch_code');
        $loop = Loop::get();
        $this->createLog('wss://ws-'.$pusherCluster.'.pusher.com/app/'.$pusherAppKey.'?protocol=7&client=js&version=7.0.6&flash=false', 'info', true, ['CONNECTION INIT']);

        \Ratchet\Client\connect('wss://ws-'.$pusherCluster.'.pusher.com/app/'.$pusherAppKey.'?protocol=7&client=js&version=7.0.6&flash=false')
            ->then(function ($connection) use ($loop, &$socketConnection, $clientId, $branchCode) {
                $connection->send('{"event":"pusher:subscribe","data":{"auth":"","channel":"ecommerce-'.$branchCode.'"}}');
                $connection->on('message', function (MessageInterface $message) use ($loop, $connection, &$pingTimer, $clientId, $branchCode) {
                    $this->eventListener($message, $connection, $clientId, $branchCode);
                });
                
                $connection->on('close', function ($code = null, $reason = null) use ($loop, &$pingTimer) {
                    $this->createLog($reason, 'warn', true, ['CONNECTION CLOSED'], [$code]);
                    $loop->stop();
                });
            }, function ($e) use ($loop) {
                Log::alert('EXCEPTION:'.json_encode($e));
                $this->error("Could not connect: {$e->getMessage()}");
                $loop->stop();
            });

        $loop->run();

        return true;
    }

    public function eventListener($message, $connection, $clientId, $branchCode)
    {
        $payload = json_decode($message);
        if (isset($payload->event)) {
            switch($payload->event) {
                case 'order' :
                    $result = app()->make(TerminalTransactionController::class)->store($payload->data);
                break;

                case 'payment' :
                    log::info($payload->data);
                    $result = app()->make(TerminalTransactionController::class)->update($payload->data);
                break;

                default: 
                break;
            }
        }
    }


}