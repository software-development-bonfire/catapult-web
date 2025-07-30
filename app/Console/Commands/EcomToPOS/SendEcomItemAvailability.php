<?php

namespace App\Console\Commands\EcomToPOS;

use App\Entities\CDISBranch;
use App\Entities\DeviceSettings;
use App\Entities\ItemAvailability;
use App\Entities\ItemAvailabilityDetail;
use App\Enums\API\DeviceType;
use App\Repositories\Contracts\ItemAvailabilityRepository;
use App\Traits\CDISRequestTrait;
use App\Traits\ConsoleCommandTrait;
use App\Traits\EcomAvailabilityTrait;
use App\Traits\ErrorLogTrait;
use App\Traits\FilenameRetryCounterTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendEcomItemAvailability extends Command
{
    use GenericHelper, StorageTrait, ErrorLogTrait, OutputBufferTrait, EcomAvailabilityTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecom:send-item-availability {--timeout=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send item availability to CDIS';

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
        $cdisUrl = getDomain(config()->get('app.cdis_url'), true);
        $branchCode = config('configuration.branch_code');


        $timeout = $this->option('timeout');
        $timeout =
            filter_var($timeout, FILTER_VALIDATE_BOOLEAN) && is_bool($timeout)
            ? (float) config('sync.pos.to_cdis.timeout')
            : ((float) $timeout
                ? filter_var($timeout, FILTER_VALIDATE_FLOAT)
                : false
            );

        if ($timeout < 0.3 || (!is_float($timeout))) {
            $this->createLog('Timeout value must be equal or greater than 0.3.', 'error', true, []);

            return;
        }

        $maximumRetry = config('sync.pos.to_cdis.max_retry') ?? 5;
        $url = $cdisUrl . '/api/catapult/v2/ecommerce/item-availability';

        while (true) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $this->createLog("Invalid URL: {$url}", 'error', true, []);
            }

            if (! $this->hasInternetConnection() && ! $this->hasInternetConnection($cdisUrl)) {
                $this->createLog(__('message.no_internet_connection'), 'error', true, []);
                sleep(10); // wait a bit before retrying
                continue;
            }

            $lastSent = Cache::get('ecom_item_availability_last_sent');
            $now = Carbon::now();
            
            if ($lastSent && $now->diffInSeconds(Carbon::parse($lastSent)) < 300) {
                sleep(10); // not yet 5 minutes, sleep and retry
                continue;
            }


            $branch = CDISBranch::where('code', $branchCode)->first();
            if (! $branch) {
                return;
            }

            $deviceSettings = DeviceSettings::where('device_type', DeviceType::ECOMMERCE)->first();
            if (!$deviceSettings) {
                return;
            }

            $items = app()->make(ItemAvailabilityRepository::class)->getDeviceAvailability($deviceSettings->bid);
            $products = [];
            foreach ($items as $item) {
                $products[] = [
                    "type" => "AUTO",
                    "product_uom_bid" => $item->product_uom_bid,
                    "branch_bid" => $branch->bid,
                    "ecomm_stock_availability" => $item->is_available,
                ];
            }
            $this->createLog('Start sending to: ' . $url, 'info', true, [
                'payload_count' => count($products)
            ]);
            $response = $this->sendRequest($products, $url);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $this->createLog($body, 'warn', true, [
                'status' => $status,
            ]);

            Cache::put('ecom_item_availability_last_sent', $now);

            $this->flushOutputBuffer();
            sleep(10); //avoid tight loop
        }
    }
}
