<?php

namespace App\Console\Commands\CDISToPOS\Schedule;

use App\Entities\CDISBranch;
use App\Entities\CDISSync;
use App\Enums\CatapultActionType;
use App\Enums\CatapultSyncStatus;
use App\Enums\CDIS\ApprovalStatus;
use App\Enums\DisplayState;
use App\Services\CDIS\SyncService;
use App\Traits\DatabaseTransaction;
use App\Traits\GenerateTrait;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Traits\JobCancellationTrait;
use App\Traits\StorageTrait;
use Illuminate\Support\Facades\Log;

class GenerateCostAndPriceChange extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait, StorageTrait, GenerateTrait;

    public $extension = 'csv';

    private $timeInterval = 5;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:csv {--interval=false}{--limit=true}{--broadcast=false}{--type=SCHEDULE}{--progress=false}{--progress_divisor=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert CDIS data (Generate CSV (Selected Table)) to specific file ';

    public $broadcast = false;
    public $processing = false;

    private $currentDate;
    private $previousDay;
    private $nextDay;
    private $branchCode;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->branchCode = config('configuration.branch_code');
        $this->timeInterval = config('sync.scheduling.time_interval');
        $this->previousDay = config('sync.scheduling.previous_day');
        $this->nextDay = config('sync.scheduling.next_day');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $nextTime = $this->getNextExecutionTime(); // Set initial delay
        while (true) {
            usleep(1000); // optional, if you want to be considerate

            // this is a preparation for upcoming changes
            // if we need to add validation to stop the scheduling
            $active = $this->checkForStopFlag();

            // Check if Catapult has running activity
            $canProceedScheduledGeneration = $this->hasNoCurrentSyncActivity();

            if ($active && $canProceedScheduledGeneration && ! $this->processing &&  microtime(true) >= $nextTime) {
                $this->processing = true;

                if ($this->hasNoCDISSyncEntries() && $this->hasNoCatapultSyncEntries()) {
                    $this->startGenerateCsv();
                }

                $nextTime = $this->getNextExecutionTime();

                $this->processing = false;
            }
        }
    }

    private function checkForStopFlag()
    {
        // completely optional
        // Logic to check for a program-exit flag
        // Could be via socket or file etc.
        // Return FALSE to stop.
        return $this->isBranchGenerated();
    }

    private function hasNoCDISSyncEntries()
    {
        $forSync = app()->make(SyncService::class)->forSync(false, 'all', false, CatapultActionType::SCHEDULE, 100, false, false);

        if (! isset($forSync->bidsChunks)) {
            return true;
        }
        return true;
    }

    private function hasNoCatapultSyncEntries()
    {
        $syncEntries = CDISSync::all();
        if (count($syncEntries) > 0) {
            return false;
        }
        return true;
    }

    /**
     * Get time for next execution
     * 
     * @return mixed
     */
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

    /**
     * Start generation of CSV once there are items
     * not yet generated that fit with the criteria
     *
     */
    private function startGenerateCsv()
    {
        $branch = CDISBranch::where('code', $this->branchCode)->first();

        $this->currentDate = Carbon::now();

        $costAndPriceChangeBids = $this->buildCostAndPriceChangeSyncEntry($branch);
        if (count($costAndPriceChangeBids) > 0) {
            if ($this->buildCostAndPriceChangeDetailSyncEntry($costAndPriceChangeBids, $branch)) {
                $options = $this->getConsoleOptions();

                $this->call('cdis:convert-data-to-file', [
                    '--interval' => $options->interval,
                    '--limit' => $options->limit,
                    '--broadcast' => $options->broadcast,
                    '--progress' => $options->progress,
                    '--progress_divisor' => $options->progress_divisor,
                    '--type' => $options->type
                ]);
            } else {
                $this->setSyncStatus(CatapultSyncStatus::PongCatapult);
            }
        } else {
            $this->createLog(__('message.no_cost_and_price_change'));
        }

        $this->processing = false;
    }

    /**
     * Build sync entry if entity data is not empty
     */
    private function buildEntitySyncEntry($entityData, $tableName, $branch)
    {
        foreach ($entityData as $entityDatum) {

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
                    'table_name' => $tableName,
                    'reference_bid' => $syncDetails->reference_bid ?? null,
                    'reference_table' => $syncDetails->reference_table ?? null,
                    'level' => 1,
                    'group' => null,
                    'code' => $this->generateRandomKey(10, 1, ''),
                    'action' => $action,
                )
            );
        }
    }

    /**
     * Build sync entry of {{cost and price change}} table
     * 
     * @return array
     */
    private function buildCostAndPriceChangeSyncEntry($branch)
    {
        $effectiveAt = now()->subDays($this->previousDay);
        if (intval($this->previousDay) <= 0) {
            $effectiveAt = now()->subYear(5);
        }
        $filters = (object) [
            'is_generated' => DisplayState::NO,
            'status' => ApprovalStatus::APPROVED,
            'effective_at' => $effectiveAt,
            'expires_at' => now()->addDays($this->nextDay),
        ];

        $syncableEntity = \App\Entities\CDISCostAndPriceChange::class;
        $entity = $this->getEntityInformation($syncableEntity);

        $entityData = app()->make($syncableEntity);

        $entityData = $this->applyCriteriaHead($entityData, $filters);
        if ($entity->has_soft_deleting) {
            $entityData = $entityData->withTrashed();
        }
        $entityData = $entityData->get();
        $costAndPriceChangeBids = [];

        if (count($entityData) > 0) {
            $this->createLog(count($entityData).' '.strtolower(Str::studly($entity->table_name)));

            $this->setSyncStatus(CatapultSyncStatus::Scheduling);

            $costAndPriceChangeBids = $entityData->pluck('bid');

            $this->buildEntitySyncEntry($entityData, $entity->table_name, $branch);
        }
        return $costAndPriceChangeBids;
    }

    /**
     * Build sync entry of {{cost and price change detail}} table
     * 
     * @return boolean
     */
    private function buildCostAndPriceChangeDetailSyncEntry($bids, $branch)
    {
        $syncableEntity = \App\Entities\CDISCostAndPriceChangeDetail::class;
        $entity = $this->getEntityInformation($syncableEntity);
        $entityData = app()->make($syncableEntity);

        $entityData = $entityData->whereIn('head_bid', $bids);
        if ($entity->has_soft_deleting) {
            $entityData = $entityData->withTrashed();
        }
        $entityData = $entityData->get();

        if (count($entityData) > 0) {
            $this->createLog(count($entityData).' '.strtolower(Str::studly($entity->table_name)));
            $this->buildEntitySyncEntry($entityData, $entity->table_name, $branch);
            return true;
        }
        return false;
    }

    /**
     * Set criteria in querying {{cost and price change}} data
     * 
     * @return mixed
     */
    private function applyCriteriaHead($entityData, $filters)
    {
        if (! empty($filters)) {
            if (isset($filters->type) && $filters->type !== '') {
                $entityData = $entityData->where('type', $filters->type);
            }
            if (isset($filters->pricing_type) && $filters->pricing_type !== '') {
                $entityData = $entityData->where('pricing_type', $filters->pricing_type);
            }
            if (isset($filters->status) && $filters->status !== '') {
                $entityData = $entityData->where('status', $filters->status);
            }
            if (isset($filters->is_generated) && $filters->is_generated !== '') {
                $entityData = $entityData->where('is_generated', $filters->is_generated);
            }
            if (! empty($filters->effective_at) && ! empty($filters->expires_at)) {
                $effectiveAt = parseDateTime($filters->effective_at, 'Y-m-d h:i:s', '');
                $expiresAt = parseDateTime($filters->expires_at, 'Y-m-d h:i:s', '');
                $entityData = $entityData->whereBetween('effective_at', ["{$effectiveAt} 00:00:00", "{$expiresAt} 23:59:59"]);
            }
        }
        return $entityData;
    }
}
