<?php

namespace App\Traits;

use App\Entities\CDISBranch;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Trait EcomAvailabilityTrait
 * @package App\Traits
 */
trait EcomAvailabilityTrait
{

    public function sendItemAvailability($productUomBid, $isAvailable)
    {
        $branchCode = config('configuration.branch_code');
        $cdisUrl = getDomain(config()->get('app.cdis_url'), true);
        $url = $cdisUrl . '/api/catapult/v2/ecommerce/item-availability';

        $branch = CDISBranch::where('code', $branchCode)->first();
        if (! $branch) {
            return;
        }
        $products[] = [
            "type" => "MANUAL",
            "product_uom_bid" => $productUomBid,
            "branch_bid" => $branch->bid,
            "ecomm_stock_availability" => toSafeBoolean($isAvailable, false) == true ? 1 : 0,
        ];

        $this->sendRequest($products, $url);
    }

    public function sendRequest($content, $uri)
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
        return $client->request('POST', $uri, $options);
    }
}
