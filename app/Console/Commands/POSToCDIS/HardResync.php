<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Enums\CatapultActionType;
use App\Enums\CatapultSyncStatus;
use App\Services\ErrorLogService;
use App\Traits\ConsoleCommandTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\PusherTrait;
use App\Traits\StorageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HardResync extends Command implements ShouldQueue
{
    use GenericHelper, OutputBufferTrait, StorageTrait, PusherTrait, ConsoleCommandTrait;

    public $errorLogService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:hard-resync {--type=HARD_RESYNC}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data files from POS FTP or Local Folder to Catapult Local';

    /**
     * Create a new command instance.
     *
     * @param ErrorLogService  $errorLogService
     * @return void
     */
    public function __construct(ErrorLogService $errorLogService)
    {
        parent::__construct();

        $this->errorLogService = $errorLogService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $timeStart = microtime(true);

        $this->initializePusher();
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $branchCode = config('configuration.branch_code');
      
        $catapultActionType = $this->option('type');

        $this->call('network:resolve');

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $convertMessage ="Success ".$executionTime;
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), CatapultSyncStatus::Resynced, $convertMessage, null);

        Log::alert(json_encode(CatapultSyncStatus::getDescription(CatapultSyncStatus::Resynced)));
        Log::alert(json_encode(CatapultActionType::getDescription($catapultActionType)));

    }
}
