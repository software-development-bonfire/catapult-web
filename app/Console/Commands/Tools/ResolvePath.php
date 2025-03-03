<?php

namespace App\Console\Commands\Tools;

use App\Entities\ApiSetup;
use App\Entities\FileStorageSetup;
use App\Helpers\WindowsBaseDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResolvePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resolve:path';

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

        $storageSetups = FileStorageSetup::all();

        foreach ($storageSetups as $setup) {
            $this->info(json_encode($setup->remote_path));
        }

        $wbd = new WindowsBaseDirectory();
        $paths = $wbd->getAllPaths();
        //$this->info(json_encode($paths));
        foreach($paths as $p => $path) {
           $this->info($p.'        = '.$path);
        }

    }
}
