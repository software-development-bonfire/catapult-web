<?php

namespace App\Traits;


use App\Repositories\Contracts\ApiSetupRepository;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Trait APISetupTrait
 * @package App\Traits
 */
trait ApiSetupTrait
{
    /**
     * Check if api setup already configured
     *
     * @return boolean
     */
    public function hasApiSetup()
    {
        try {
            if (! Schema::hasTable('api_setups')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $apiEndpoints = app()->make(ApiSetupRepository::class)->list([]);
        return (count($apiEndpoints) > 0);
    }

    /**
     * Update domain name on api setup endpoints
     */
    public function updateApiDomain($cdisUrl)
    {
        // Check if provided CDIS URL ends with /
        // then remove if exist
        if (Str::endsWith($cdisUrl, '/')) {
            $cdisUrl = rtrim($cdisUrl, "/");
        }
        $repository = app()->make(ApiSetupRepository::class);
        $apiEndpoints = $repository->list([]);
        if (count($apiEndpoints) > 0) {
            foreach ($apiEndpoints as $endpoint) {
                $endpoint = (object) $endpoint;
                $this->line("Updating <{$endpoint->end_point}>");

                // parse existing endpoint and get the url path
                // then concatenation with new host from CDIS URL
                $pieces = parse_url($endpoint->end_point);
                if (isset($pieces['path'])) {
                    $path = $pieces['path'];
                    $newEndpoint = "{$cdisUrl}{$path}";
                }

                $apiSetup = $repository->find($endpoint->bid);
                if ($apiSetup) {
                    $apiSetup->update([
                        'end_point' => $newEndpoint
                    ]);
                    $this->info("Updated to <{$newEndpoint}>");
                }
            }
        }
    }
}
