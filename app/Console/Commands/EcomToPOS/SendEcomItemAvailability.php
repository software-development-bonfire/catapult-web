<?php

namespace App\Console\Commands\EcomToPOS;

use App\Entities\CDISBranch;
use App\Entities\DeviceSettings;
use App\Entities\ItemAvailability;
use App\Enums\API\DeviceType;
use App\Traits\CDISRequestTrait;
use App\Traits\ConsoleCommandTrait;
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
    use GenericHelper, StorageTrait, ErrorLogTrait, FilenameRetryCounterTrait, OutputBufferTrait, CDISRequestTrait, ConsoleCommandTrait;

    public $extension = '.json';
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

    private $fileContentErrors = [];

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
            if (! $this->hasInternetConnection() && ! $this->hasInternetConnection($cdisUrl)) {
                $this->setErrorLineLog(__('message.no_internet_connection'));
                sleep(10); // wait a bit before retrying
                continue;
            }

            $lastSent = Cache::get('ecom_item_availability_last_sent');
            $now = Carbon::now();

            if ($lastSent && $now->diffInSeconds(Carbon::parse($lastSent)) < 300) {
                sleep(10); // not yet 5 minutes, sleep and retry
                continue;
            }

            $deviceSettings = DeviceSettings::where('device_type', DeviceType::ECOMMERCE)->first();
            if (!$deviceSettings) {
                return;
            }
            $branch = CDISBranch::where('code', $branchCode)->first();
            if (! $branch) {
                return;
            }

            $items = ItemAvailability::with('itemAvailabilityDetail')
                ->whereHas('itemAvailabilityDetail', function ($query) use ($deviceSettings) {
                    $query->where('device_settings_bid', $deviceSettings->bid);
                })
                ->get();

            $products = [];
            foreach ($items as $item) {
                $products[] = [
                    "product_uom_bid" => $item['product_uom_bid'],
                    "branch_bid" => $branch->bid,
                    "ecomm_stock_availability" => $item['is_available'],
                ];
            }
            $this->createLog('Start sending to: ' . $url, 'info', true, [
                'payload_count' => count($products)
            ]);
            $this->send($products, $url);

            Cache::put('ecom_item_availability_last_sent', $now);

            $this->flushOutputBuffer();
            sleep(10); // Optional: avoid tight loop
        }
    }

    public function send($content, $uri)
    {
        $client = new Client([
            'verify' => false,
            'http_errors' => false,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $options = [
            'json' => [
                'data' => $content
            ],
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        $response = $client->request('POST', $uri, $options);

        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        $this->createLog($body, 'warn', true, [
            'status' => $status,
        ]);

        return $response;
    }
}
