<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISBranch;
use App\Entities\CDISSync;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMapping;
use App\Enums\CatapultSyncStatus;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Exports\CDIS\DataConversionToExcel;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CDIS\SyncService;
use App\Traits\JobCancellationTrait;
use App\Traits\StorageTrait;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ConvertDataToFileAll extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait, StorageTrait;

    public $extension = 'csv';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:convert-data-to-file-all {--interval=true}{--limit=true}{--broadcast=false}{--type=ALL}{--progress=false}{--progress_divisor=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert CDIS data (Generate CSV (All Data)) to specific file ';

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
        $timeStart = microtime(true);

        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $progressDivisor = config('sync.cdis.to_catapult.progress_divisor');
        $branchCode = config('configuration.branch_code');

        $branch = CDISBranch::where('code', $branchCode)->first();

        $interval = $this->option('interval');
        $interval = toBooleanOrInt($interval, config('sync.cdis.to_catapult.interval'));

        $limit = config('sync.cdis.to_catapult.convert_limit');

        $broadcast = $this->option('broadcast');
        $broadcast = toBooleanOrInt($broadcast, config('sync.cdis.to_catapult.broadcast'));

        $showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);

        $catapultActionType = $this->option('type');

        $progressDivisor = $this->option('progress_divisor');
        $progressDivisor = toBooleanOrInt($progressDivisor, config('sync.cdis.to_catapult.progress_divisor'));

        Cache::forget('excludedEntries');
        Cache::forget('excludedSyncBids');

        $this->createLog(
            __('info.conversion_started'),
            'info',
            true
        );

        // Conversion already been executed
        $this->setConverting();
        $this->setSyncStatus(CatapultSyncStatus::Converting);

        $this->mappingVariable = [];

        if ($broadcast) {
            $this->initializePusher();
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.converting_all_data'), null);
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  [
                'state' => CatapultSyncStatus::Converting, 
                'code' => $branchCode, 
                'description' => $catapultActionType
            ], null);
        }

        $syncEntries = app()->make(SyncEntryRepository::class)
            ->list((object) array('type' => MappingType::CDIS_TO_POS));

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
            Cache::forget('file_storage_setup_'.$syncEntry->name);
        }      

        $syncService = app()->make(SyncService::class);
        $convertableEntities = $syncService->getArrangedSyncableEntities();
        $syncableEntitiesGroupBy = $syncService->getSyncableEntitiesGroupBy();

        $hasBeenCancelled = $this->hasBeenCancelledConversion();;

        CDISSync::truncate();

        if ($broadcast) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.constructing_tables', ['table_count' => count($convertableEntities)]), null);
        }

        $entityCountProgress = 0;
        foreach ($convertableEntities as $syncableEntity) {
            $hasBeenCancelled = $this->hasBeenCancelledConversion();
            if ($hasBeenCancelled) {
                break;
            }

            $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($syncableEntity));
            $entityData = app()->make($syncableEntity);

            if ($hasSoftDeleting) {
                $entityData = $entityData->withTrashed();
            }

            if (isset($syncableEntitiesGroupBy[$syncableEntity])) {
                $entityData = $entityData
                    ->groupBy($syncableEntitiesGroupBy[$syncableEntity])
                    ->orderBy('id', 'DESC');
                    
                $entityData = $entityData->get()->unique('product_uom_bid');
            } else {
                $entityData = $entityData->get();
            }

            $entityName = str_replace('App\\Entities\\CDIS', '', $syncableEntity);
            $tableName =  Str::snake($entityName);

            $entityCountProgress++;
            $progress = 0;

            foreach ($entityData as $entityDatum) {
                $hasBeenCancelled = $this->hasBeenCancelledConversion();
                if ($hasBeenCancelled) {
                    break;
                }

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

                if ($this->modelHasColumn($entityDatum, $tableName, 'branch_bid')) {
                    if ($entityDatum->branch_bid !== $branch->bid) {
                        continue;
                    }
                }

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

                $progress++;

            }
            if ($broadcast) {
                $this->pusher->trigger(
                    $this->cdisAndCatapultSyncChannel($branchCode), 
                    'Converting',
                    __('info.constructing_tables_progress', ['progress' => $entityCountProgress, 'table_count' => count($convertableEntities)]),
                        null
                );
            }
            $this->createLog( __('info.table_constructed', ['table' => $entityName, 'count' => count($entityData), 'progress' => $entityCountProgress, 'table_count' => count($convertableEntities)]));
        }

        CDISSync::where('branch_bid','!=', $branch->bid)->delete();

        $commandOptions = [
            '--interval' => $interval,
            '--limit' => $limit,
            '--broadcast' =>  $broadcast,
            '--progress' => $showProgress,
            '--progress_divisor' => $progressDivisor,
            '--type' => $catapultActionType
        ];

        $jobs = DB::table('jobs')->get();

        if (count($jobs) > 0) {
            Artisan::queue('cdis:convert-data-to-file', $commandOptions);
        } else {
            Artisan::call('cdis:convert-data-to-file', $commandOptions);
        }
    }
}
