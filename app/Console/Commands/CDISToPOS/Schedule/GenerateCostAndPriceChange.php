<?php

namespace App\Console\Commands\CDISToPOS\Schedule;

use App\Entities\CDISBranch;
use App\Entities\CDISSync;
use App\Enums\CatapultSyncStatus;
use App\Enums\CDIS\CostAndPriceChangeType;
use App\Enums\DisplayState;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Repositories\Contracts\CostAndPriceChangeRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\CDIS\SyncService;
use App\Traits\JobCancellationTrait;
use App\Traits\StorageTrait;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GenerateCostAndPriceChange extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait, StorageTrait;

    public $extension = 'csv';

    private $timeInterval = 1;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:csv {--interval=true}{--limit=true}{--broadcast=false}{--type=ALL}{--progress=false}{--progress_divisor=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert CDIS data (Generate CSV (Selected Table)) to specific file ';

    public $broadcast = false;
    private $mappingVariable = [];

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
        $active = true;

        $nextTime = $this->getNextExecutionTime(); // Set initial delay

        while ($active) {
            usleep(1000); // optional, if you want to be considerate

            $canProceedScheduledGeneration = $this->canExecuteScheduledGeneration();

            if ($canProceedScheduledGeneration && microtime(true) >= $nextTime) {

                $this->startGeneration();

                $nextTime = $this->getNextExecutionTime();
            }

            // Do other stuff (you can have as many other timers as you want)           

            // this is a preparation for upcoming changes
            // if we need to add validation to stop the scheduling
            $active = $this->checkForStopFlag();
        }
    }

    private function checkForStopFlag()
    {
        // completely optional
        // Logic to check for a program-exit flag
        // Could be via socket or file etc.
        // Return FALSE to stop.
        return true;
    }

    private function canExecuteScheduledGeneration()
    {
        // we must check if there is an activity from/to CDIS
        // to block the execution of this scheduling
        $currentSyncStatus = $this->getSyncStatus();
        if (
            $currentSyncStatus != CatapultSyncStatus::Fetching &&
            $currentSyncStatus != CatapultSyncStatus::Syncing &&
            $currentSyncStatus != CatapultSyncStatus::Converting
        ) {
            return true;
        }
        return false;
    }

    private function getNextExecutionTime()
    {
        // add current time with defined time interval
        // this will the time for next execution
        return microtime(true) + ($this->timeInterval * 60);
    }

    private function getConsoleOptions()
    {
        $progressDivisor = config('sync.cdis.to_catapult.progress_divisor');

        $interval = $this->option('interval');
        $limit = config('sync.cdis.to_catapult.convert_limit');
        $broadcast = $this->option('broadcast');
        $showProgress = $this->option('progress');
        $catapultActionType = $this->option('type');
        $progressDivisor = $this->option('progress_divisor');

        return (object) [
            'interval' => toBooleanOrInt($interval, config('sync.cdis.to_catapult.interval')),
            'limit' => $limit,
            'broadcast' => toBooleanOrInt($broadcast, config('sync.cdis.to_catapult.broadcast')),
            'progress' => filter_var($showProgress, FILTER_VALIDATE_BOOLEAN),
            'progress_divisor' => toBooleanOrInt($progressDivisor, config('sync.cdis.to_catapult.progress_divisor')),
            'type' => $catapultActionType,
        ];
    }

    private function startGeneration()
    {

        $currentDate = Carbon::now();
        $filters = (object) [
            'type' => CostAndPriceChangeType::TIME_TRIGGER,
           // 'status' => Status::ACTIVE,
            'is_generated' => DisplayState::NO,
            'effective_at' => now()->subDays(2),
            'expires_at' => now()->addDays(2),
        ];
        $costAndPriceChanges = app()->make(CostAndPriceChangeRepository::class)->list($filters);

        
        if (count($costAndPriceChanges) > 0) {
            $this->createLog(count($costAndPriceChanges).' cost and price change');
            Log::alert(json_encode($costAndPriceChanges));

            $branchCode = config('configuration.branch_code');
            $branch = CDISBranch::where('code', $branchCode)->first();

            $syncableEntity = \App\Entities\CDISCostAndPriceChange::class;
            $entityName = str_replace('App\\Entities\\CDIS', '', $syncableEntity);

            $tableName =  Str::snake($entityName);
            foreach ($costAndPriceChanges as $entityDatum) {
               
                $action = 'create';
                if ($this->modelHasColumn($entityDatum, $tableName, 'deleted_at')) {
                    if ($entityDatum->deleted_at !== null) {
                        $action = 'delete';
                    } else {
                        if (
                            $this->modelHasColumn($entityDatum, $tableName, 'created_at') &&
                            $this->modelHasColumn($entityDatum, $tableName, 'updated_at')
                        ) {
                            if ($entityDatum->created_at !== $entityDatum->updated_at) {
                                $action = 'update';
                            }
                        }
                    }
                } else {
                    if (
                        $this->modelHasColumn($entityDatum, $tableName, 'created_at') &&
                        $this->modelHasColumn($entityDatum, $tableName, 'updated_at')
                    ) {
                        if ($entityDatum->created_at !== $entityDatum->updated_at) {
                            $action = 'update';
                        }
                    }
                }
                $syncDetails = $entityDatum->syncDetails();

                CDISSync::create(
                    array(
                        'branch_bid' => $branch->bid,
                        'table_bid' => $entityDatum->bid,
                        'table_name' =>  $tableName,
                        'reference_bid' => $syncDetails->reference_bid ?? null,
                        'reference_table' => $syncDetails->reference_table ?? null,
                        'level' => 1,
                        'group' => null,
                        'code' => $this->generateRandomKey(10, 1, ''),
                        'action' => $action,
                    )
                );
            }

            $options = $this->getConsoleOptions();

            Artisan::queue('cdis:convert-data-to-file', [
                '--interval' => $options->interval,
                '--limit' => $options->limit,
                '--broadcast' =>  $options->broadcast,
                '--progress' => $options->progress,
                '--progress_divisor' => $options->progress_divisor,
                '--type' => $options->type
            ]);
        } else {
            $this->createLog($currentDate);
        }
    }
}
