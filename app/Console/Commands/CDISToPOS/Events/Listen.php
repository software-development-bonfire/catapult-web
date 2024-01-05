<?php

namespace App\Console\Commands\CDISToPOS\Events;

use App\Enums\CatapultActionType;
use App\Enums\CatapultHandshaking;
use App\Enums\CatapultSyncStatus;
use App\Enums\DefinedQueueName;
use App\Helpers\CustomPinger as Ping;
use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;

class Listen extends Command
{
    use GenericHelper, PusherTrait, JobCancellationTrait;
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
        $cdisUrl = urlToDomain(config()->get('app.cdis_url'));
        $pusherDomain = 'ws-eu.pusher.com';

        while (true) {
            if (
                $this->hasInternetConnection() &&
                $this->hasInternetConnection($cdisUrl) &&
                $this->hasInternetConnection($pusherDomain)
            ) {
                $this->connect();
            } else {
                $this->createLog("Could not connect: No internet connection.", 'warn', true, ['CONNECTION ERROR']);
                $this->executeNetworkResolve();
            }

            sleep(5);
        }
    }

    public function connect()
    {
        $pusherAppKey = config('broadcasting.connections.cdis_pusher.key');
        $clientId = config('configuration.client_id');
        $branchCode = config('configuration.branch_code');
        $loop = Loop::get();
        $this->createLog('wss://ws-eu.pusher.com/app/'.$pusherAppKey.'?protocol=7&client=js&version=7.0.6&flash=false', 'info', true, ['CONNECTION INIT']);

        \Ratchet\Client\connect('wss://ws-eu.pusher.com/app/'.$pusherAppKey.'?protocol=7&client=js&version=7.0.6&flash=false')
            ->then(function ($connection) use ($loop, &$socketConnection, $clientId, $branchCode) {
                $connection->send('{"event":"pusher:subscribe","data":{"auth":"","channel":"'.$this->cdisAndCatapultSyncChannel($branchCode).'"}}');

                $pingTimer = $this->getPingTimer($loop, $connection);

                $connection->on('message', function (MessageInterface $message) use ($loop, $connection, &$pingTimer, $clientId, $branchCode) {
                    $loop->cancelTimer($pingTimer);
                    $pingTimer = $this->getPingTimer($loop, $connection);
                    $this->eventListener($message, $connection, $clientId, $branchCode);
                });

                $connection->on('close', function ($code = null, $reason = null) use ($loop, &$pingTimer) {
                    $this->createLog($reason, 'warn', true, ['CONNECTION CLOSED'], [$code]);
                    $loop->cancelTimer($pingTimer);
                    $loop->stop();

                    gc_collect_cycles();
                });
            }, function ($e) use ($loop) {
                Log::alert('EXCEPTION:'.json_encode($e));
                $this->error("Could not connect: {$e->getMessage()}");
                $loop->stop();

                $this->executeNetworkResolve();
            });

        $loop->run();

        return true;
    }

    private function getPingTimer($loop, $connection)
    {
        return $loop->addPeriodicTimer(120, function () use ($loop, $connection) {
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
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'PongCatapult', '{}', $this->socketId, true); 
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), CatapultHandshaking::ACK, '{}', $this->socketId, true);
                    break;

                case "pusher:error":
                    $this->createLog($payload->data->message, 'warn', true, ['PUSHER', 'ERROR'], [$payload->data->code]);
                    break;

                case "pusher:pong":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::PongCatapult, '{}', $payload);
                    if ($this->hasNoCurrentSyncActivity()) {
                        $this->updateCatapultStatus(CatapultSyncStatus::Online, $branchCode);
                    }
                    
                    break;

                case "App\Events\Catapult\Ping":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::PongCatapult, '{}', $payload);
                    $this->createLog(json_encode($payload), 'warn', true, ['EVENT', 'PongCatapult']);
                    break;

                case "App\Events\Catapult\Handshake":
                    $data = (object) json_decode($payload->data);
                    if (! empty($data->handShake)) {
                        if ($data->handShake === CatapultHandshaking::SYN) {
                            $this->triggerPusher($branchCode, CatapultHandshaking::ACK, json_encode($data), $payload);
                            $this->createLog(json_encode($payload), 'warn', true, ['EVENT', CatapultHandshaking::ACK]);
                        }
                    }
                    break;
            
                case "App\Events\Catapult\TriggerCDISFetchDataForSync":
                    $this->createLog(json_encode($payload), 'info', true, ['EVENT', $payload->event]);

                    if ($this->hasNoCurrentSyncActivity()) {

                        if (config('sync.cdis.clear_jobs')) {
                            $this->call('clear:jobs');
                        }

                        $options = (object)$this->getPayloadOptions($payload);
                        Artisan::queue('cdis:fetch-data-for-sync', [
                            '--interval' => $options->interval,
                            '--limit' => $options->limit,
                            '--broadcast' =>  $options->broadcast,
                            '--progress' => $options->progress,
                            '--progress_divisor' => $options->progress_divisor,
                            '--type' => $options->type
                        ]);
                    } else {
                        $this->createLog(json_encode($payload), 'info', true, ['ACTIVITY', $this->getSyncStatus()]);
                    }
                   
                    break;

                case "App\Events\Catapult\TriggerCDISDataConversion":
                    $options = (object)$this->getPayloadOptions($payload);

                    $commandOptions =  [
                        '--interval' => $options->interval,
                        '--limit' => $options->limit,
                        '--broadcast' => $options->broadcast,
                        '--progress' => $options->progress,
                        '--progress_divisor' => $options->progress_divisor,
                        '--type' => $options->type
                    ];

                    if (isset($options->refetchForSync) && $options->refetchForSync) {
                        $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.resyncing'), $payload);
                        Artisan::queue('cdis:fetch-data-for-sync-again', $commandOptions);
                    } else {
                        $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.converting'), $payload);

                        $convertCommand = null;
                        if ($options->type === CatapultActionType::NEW_BRANCH) {
                            $convertCommand = 'cdis:convert-data-to-file';
                        } else if ($options->type === CatapultActionType::ALL) {
                            $convertCommand = 'cdis:convert-data-to-file-all';
                        } else {
                            $convertCommand = 'cdis:convert-data-to-file-changes';
                        }

                        Artisan::queue($convertCommand, $commandOptions);
                    }
                    break;

                case "App\Events\Catapult\CancelCDISDataConversion":
                    $options = (object)$this->getPayloadOptions($payload);

                    if ($this->getSyncStatus() === CatapultSyncStatus::Converting) {
                        $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.conversion_cannot_be_cancelled'), $payload);
                    } else {
                        $this->triggerPusher($branchCode, CatapultSyncStatus::CancelConversion, __('info.cancelling'), $payload);
                        Artisan::call('cdis:cancel-convert', [
                            '--retry' => '10',
                            '--broadcast' => $options->broadcast,
                            '--progress' => $options->progress,
                            '--type' => $options->type
                        ]);
                    }
                    break;

                case "App\Events\Catapult\CancelCDISFetchDataForSync":
                    $options = (object)$this->getPayloadOptions($payload);
                    $this->triggerPusher($branchCode, CatapultSyncStatus::CancelSyncing, __('info.cancelling'), $payload);
                    Artisan::call('cdis:cancel-sync', [
                        '--retry' => '10',
                        '--broadcast' => $options->broadcast,
                        '--progress' => $options->progress,
                        '--type' => $options->type
                    ]);
                    break;

                case "App\Events\Catapult\TriggerHardResync":
                    $options = (object)$this->getPayloadOptions($payload);
                    Artisan::call('pos:hard-resync', [
                        '--type' => $options->type,
                        '--user_bid' => $options->userBid
                    ]);
                    break;

                case "App\Events\Catapult\ForwardMissingTransaction":
                    $data = (object) json_decode($payload->data);
                    if (! empty($data->transactions)) {
                        $options = (object)$this->getPayloadOptions($payload);
                        Artisan::call('cdis:missing-transaction', [
                            '--user_bid' => $options->userBid,
                            '--transactions' => $data->transactions
                        ]);
                        $this->createLog(json_encode($data), 'info', true, ['EVENT', $payload->event]);
                    }
                    break;
                default:
                    $this->createLog(json_encode($payload), 'info', true, ['EVENT', $payload->event]);
                    break;
            }
        }
    }

    public function triggerPusher($branchCode, $state, $message, $payload, $logType = 'info')
    {
        $description = null;
        if (! empty($payload)) {
            $this->createLog(json_encode($payload), $logType, true, ['EVENT', $payload->event]);

            if (! empty($payload->data)) {
                $data = (object) json_decode($payload->data);
                if (! empty($data->catapultActionType)) {
                    $description = $data->catapultActionType;
                }
            }
        }
        $this->updateCatapultStatus($state, $branchCode, $description);
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), $state, $message, $this->socketId, true);
    }

    public function updateCatapultStatus($state, $branchCode, $description = null)
    {
        if ($state !== CatapultSyncStatus::Online && $state !== CatapultHandshaking::SYN && $state !== CatapultHandshaking::ACK) {
            $this->setSyncStatus($state);
        }
        if ($state === CatapultSyncStatus::PongCatapult) {
            $state = CatapultSyncStatus::Online;
        }
        if ($state === CatapultHandshaking::SYN || $state === CatapultHandshaking::ACK) {
           return;
        }
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status', ['state' => $state, 'code' => $branchCode, 'description' => $description], $this->socketId, true);
    }

    private function getPayloadOptions($payload)
    {
        $result = [];
        $result['interval'] = false;
        $result['progress'] = false;
        $result['broadcast'] = true;
        $result['refetchForSync'] = false;
        $result['max_limit'] = config('sync.cdis.to_catapult.convert_limit');
        $result['progress_divisor'] = config('sync.cdis.to_catapult.progress_divisor');

        if ($payload->data !== null) {
            $data = (object) json_decode($payload->data);
            if (! empty($data->catapultActionType)) {
                $result['type'] = $data->catapultActionType;
            }
            if (! empty($data->refetchForSync)) {
                $result['refetchForSync'] = $data->refetchForSync;
            }
            if (! empty($data->userBid)) {
                $result['userBid'] = $data->userBid;
            }
            if (! empty($data->options)) {
                $options = $data->options;
                if (! empty($options->interval)) {
                    $result['interval'] = $options->interval;
                }
                if (! empty($options->limit)) {
                    $result['limit'] = $options->limit;
                }
                if (! empty($options->max_limit)) {
                    $result['max_limit'] = $options->max_limit;
                }
                if (! empty($options->broadcast)) {
                    $result['broadcast'] = $options->broadcast;
                }
                if (! empty($options->show_progress)) {
                    $result['progress'] = $options->show_progress;
                }
                if (! empty($options->progress_divisor)) {
                    $result['progress_divisor'] = $options->progress_divisor;
                }
            }
        }
        return (object) $result;
    }

    private function executeNetworkResolve()
    {
        if ($this->isResolvedBeenExecuted()) {
            $this->resolvedCount = $this->checkResolvedStatus();
        } else {
            $this->setResolveStatus(true, $this->resolvedCount);
            $this->callSilent('network:resolve');
            $this->resolvedCount++;
        }
    }
}
