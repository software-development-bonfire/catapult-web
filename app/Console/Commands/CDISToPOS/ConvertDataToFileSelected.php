<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISBranch;
use App\Entities\CDISSync;
use App\Enums\CatapultSyncStatus;
use App\Enums\MappingType;
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

class ConvertDataToFileSelected extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait, StorageTrait;

    public $extension = 'csv';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:convert-data-to-file-selected {--interval=true}{--limit=true}{--broadcast=false}{--type=ALL}{--progress=false}{--progress_divisor=100}';

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
        $convertableEntities = $syncService->getSelectedSyncableEntities();

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

            $entityName = str_replace('App\\Entities\\CDIS', '', $syncableEntity);

            $tableName =  Str::snake($entityName);

            $entityData = $entityData->get();

            $entityCountProgress++;
            $this->buildEntitySyncEntry($entityData, $tableName, $branch, true, true);

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

        Artisan::queue('cdis:convert-data-to-file', [
            '--interval' => $interval,
            '--limit' => $limit,
            '--broadcast' =>  $broadcast,
            '--progress' => $showProgress,
            '--progress_divisor' => $progressDivisor,
            '--type' => $catapultActionType
        ]);
    }
}
