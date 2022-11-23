<?php

namespace App\Console\Commands\CDISToPOS\Events;

use App\Enums\CatapultSyncStatus;
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
                try {
                    $this->connect();
                } catch (\Exception $e) {
                    if ($this->isResovedBeenExecuted()) {
                        $this->checkResolvedStatus();
                    } else {
                        $this->setResolveStatus('Resolved', $this->resolvedCount);
                        Artisan::call('network:resolve');
                        $this->resolvedCount++;
                    }
                }
            } else {
                $this->createLog("Could not connect: No internet connection.", 'warn', true, ['CONNECTION ERROR']);
            }

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
                $connection->send('{"event":"pusher:subscribe","data":{"auth":"","channel":"'.$this->cdisAndCatapultSyncChannel($branchCode).'"}}');

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
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'PongCatapult', '{}', $this->socketId, true);
                    break;

                case "pusher:error":
                    $this->createLog($payload->data->message, 'warn', true, ['PUSHER', 'ERROR'], [$payload->data->code]);
                    break;

                case "App\Events\Catapult\Ping":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::PongCatapult, '{}', $payload);
                    $this->createLog(json_encode($payload), 'warn', true, ['EVENT', 'PongCatapult']);
                    break;

                case "App\Events\Catapult\TriggerCDISFetchDataForSync":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing,  __('info.syncing_to_catapult'), $payload);
                    Artisan::queue('cdis:fetch-data-for-sync', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                    break;
					
                case "App\Events\Catapult\TriggerCDISFetchDataForSyncManual":                   
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.syncing_to_catapult'), $payload);
                    Artisan::queue('cdis:fetch-data-for-sync-event', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\TriggerCDISFetchDataForSyncPerEvent":
                    if ($this->getSyncStatus() !== CatapultSyncStatus::Syncing) {
                        $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.syncing'), $payload);
                        Artisan::queue('cdis:fetch-data-for-sync-event', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                    }
                    break;

                case "App\Events\Catapult\TriggerCDISDataConversion":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.converting'), $payload);
                    Artisan::queue('cdis:convert-data-to-file', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\TriggerCDISDataConversionPerEvent":
                    if ($payload->data !== null) {
                        $data = json_decode($payload->data);
                        if (isset($data->refetchForSync) && $data->refetchForSync) {
                            $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.syncing'), $payload);
                            Artisan::queue('cdis:fetch-data-for-sync-again', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false', '--type' => 'changes']);
                        } else {
                            $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.converting'), $payload);
                            Artisan::queue('cdis:convert-data-to-file-event', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                        }
                    }
                    break;

                case "App\Events\Catapult\TriggerCDISDataConversionAll":
                    if ($payload->data !== null) {
                        $data = json_decode($payload->data);
                        if (isset($data->refetchForSync) && $data->refetchForSync) {
                            $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.syncing'), $payload);
                            Artisan::queue('cdis:fetch-data-for-sync-again', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false', '--type' => 'all']);
                        } else {
                            $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.converting'), $payload);
                            Artisan::queue('cdis:convert-data-to-file-all', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
                        }
                    }
                    break;

                case "App\Events\Catapult\CancelCDISDataConversion":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.cancelling'), $payload);
                    Artisan::call('cdis:cancel-convert', ['--retry' => '10', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\CancelCDISDataConversionEvent":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.cancelling'), $payload);
                    Artisan::call('cdis:cancel-convert-event', ['--retry' => '10', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\CancelCDISDataConversionAll":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Converting, __('info.cancelling'), $payload);
                    Artisan::call('cdis:cancel-convert-all', ['--retry' => '10', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\CancelCDISFetchDataForSync":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::Syncing, __('info.cancelling'), $payload);
                    Artisan::call('cdis:cancel-sync', ['--retry' => '10', '--broadcast' => 'true', '--progress' => 'false']);
                    break;

                case "App\Events\Catapult\TriggerCDISFetchDataForSyncManualDone":
                    $this->triggerPusher($branchCode, CatapultSyncStatus::SyncDone, __('info.syncing_to_catapult_success'), $payload);
                    break;

                default:
                    $this->createLog(json_encode($payload->data), 'info', true, ['EVENT', $payload->event]);
                    $this->updateCatapultStatus(CatapultSyncStatus::Online, $branchCode);
                    break;
            }
        }
    }

    public function triggerPusher($branchCode, $state, $message, $payload, $logType = 'info')
    {
        if (! empty($payload)) {
            $this->createLog(json_encode($payload),  $logType, true, ['EVENT', $payload->event]);
        }
        $this->updateCatapultStatus($state, $branchCode);
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), $state, $message, $this->socketId, true);
    }

    public function updateCatapultStatus($state, $branchCode)
    {
        if ($state !== CatapultSyncStatus::Online) {
            $this->setSyncStatus($state);
        }
        if ($state === CatapultSyncStatus::PongCatapult) {
            $state = CatapultSyncStatus::Online;
        }
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  ['state' => $state, 'code' => $branchCode],  $this->socketId, true);
    }
}
