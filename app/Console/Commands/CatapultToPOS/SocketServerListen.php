<?php

namespace App\Console\Commands\CatapultToPOS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ratchet\Http\HttpServer;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;

class SocketServerListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:server-listen {--interval=true}{--limit=true}';

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
        $server = IoServer::factory(
            new Chat()
          , 6010
        );
        */
/*
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            8080
        );
    
    
        $server->run();
        */
        
    }
}