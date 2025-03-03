<?php

namespace App\Console\Commands\Tools;

use App\Entities\ApiSetup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResolveAPIUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resolve:api-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update end_point URLs in api_setups table with new domain from .env file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cdisUrl = config('app.cdis_url'); 

        if (!$cdisUrl) {
            $this->error('CDIS_URL is not set in .env file.');
            return;
        }

        $this->info('CDIS_URL: '.$cdisUrl.' on .ENV file');

        $apiSetups = ApiSetup::all();

        foreach ($apiSetups as $setup) {
            $oldEndpoint = $setup->end_point;

            // Extracting the path from the current endpoint
            $urlParts = parse_url($oldEndpoint);
            if (!isset($urlParts['path'])) {
                $this->error('Invalid URL format: '.$oldEndpoint);
                continue;
            }
            $path = $urlParts['path'];

            // Form the new endpoint
            $newEndpoint = rtrim($cdisUrl, '/') . $path;

            // Skip if no change
            if ($oldEndpoint === $newEndpoint) {
                $this->info('    No change for: '.$oldEndpoint);
                continue;
            }

            // Update the end_point column
            $setup->end_point = $newEndpoint;
            $setup->save();

            $this->info('    Updated:');
            $this->info('        From: '.$oldEndpoint);
            $this->info('        To:   '.$newEndpoint);
        }

        $this->info('All endpoints have been updated.');
    }
}
