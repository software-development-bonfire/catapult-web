<?php

namespace App\Console\Commands\CDISToPOS;

use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelConvertDataToFile extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cancel-convert {--retry=5}{--broadcast=false}{--progress=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel conversion process.';

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
        $retryCount = $this->option('retry');
        $retryCount = filter_var($retryCount, FILTER_VALIDATE_INT) ? (int) $retryCount: 0;

        $broadcast = $this->option('broadcast');
        $broadcast = filter_var($broadcast, FILTER_VALIDATE_BOOLEAN);

		if ($broadcast) {
			$this->initializePusher();
		}

        $timeStart = microtime(true);

        $this->setCancelledConversion();
        $attempt = 0;
        while(! $this->hasBeenCancelledConversion()) {            
            $this->setCancelledConversion();
            if ($attempt <= $retryCount) {
                $attempt++;
            } else {
                break;
            }
            $hasBeenCancelled = $this->hasBeenCancelledConversion();
            $this->createLog('Has been cancelled? '.$hasBeenCancelled, 'info', true);
            $this->createLog('Cancelling attempt @ '.$attempt, 'info', true);
        }

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $this->createLog('Cancelling takes @ '.$this->secondsToHumanReadableTime($executionTime), 'info', true);
    }
}
