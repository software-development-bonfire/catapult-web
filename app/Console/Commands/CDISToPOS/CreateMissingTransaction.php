<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISBranch;
use App\Enums\CatapultSyncStatus;
use App\Exports\CDIS\DataConversionToExcel;
use App\Repositories\Contracts\FileStorageSetupRepository;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Traits\JobCancellationTrait;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CreateMissingTransaction extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, OutputBufferTrait, StorageTrait;

    public $extension = 'csv';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:missing-transaction {--type=FORWARD} {--user_bid=null} {--transactions=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CSV file for the list of missing transactions';

    public $definedTargetFolder;
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

        $this->initializePusher();

        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $catapultActionType = $this->option('type');
        $userBid            = $this->option('user_bid');
        $transactions       = $this->option('transactions');

        $branchCode = config('configuration.branch_code');
        $definedTargetFolder = __('label.branch_and_terminal');
        $definedTargetFilename = __('label.missing_transaction_numbers');

        $fileStorageSetup = app()->make(FileStorageSetupRepository::class)->where('name', 'CDIS TO POS (DEFAULT)')->first();
        $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::CDIS_FORWARD);

        if (isset($selectedDisk) && is_array($selectedDisk)) {
            $localDiskName = $selectedDisk['localDiskName'];
        } else {
            return false;
        }
        $localDisk = Storage::disk($localDiskName);
        $headers = ['No', 'Transactions'];
        $rows = [];
        $count = 1;
        foreach ($transactions as $transaction) {
            $rows[] = [$count, $transaction];
            $count += 1;
        }

        $filename = str_replace(' ', '_', $definedTargetFilename);
        $filePath = "/{$definedTargetFolder}/{$branchCode}/{$filename}.{$this->extension}";

        Excel::store(
            new DataConversionToExcel($headers, $rows, $this->extension),
            $filePath,
            $localDiskName
        );

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $message = [
            'execution' => $this->secondsToHumanReadableTime($executionTime),
            'type' => $catapultActionType,
            'userBid' => $userBid,
        ];
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), CatapultSyncStatus::Forwarding, $message, null);

        $this->flushOutputBuffer();
        sleep(5);
    }
}
