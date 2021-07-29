<?php

namespace App\Services;

use App\Entities\CDISSync;
use App\Entities\Configuration;
use App\Entities\EntryCounter;
use App\Entities\FolderCounter;
use App\Enums\ApiEndpoint;
use App\Enums\MappingType;
use App\Exports\CDISDataToCSV;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CdisToCsvFileService
{
    /**
     * @param  int     $counter
     * @param  array   $header
     * @param  array   $mapped
     * @param  object  $cdisSync
     * @param  array   $endpoint
     * @param  string  $action
     * @param  string  $disk
     * @param  boolean $grouped
     *
     * @return mixed
     */
    public function saveToFTP($counter, $header, $mapped, $cdisSync, $endpoint, $action, $disk)
    {
        $entryLimit = Configuration::where('attribute', 'cdis_to_pos_entry_limit')->first();

        $converted = false;
        if ($counter <= $entryLimit->value) {
            $converted = Excel::store(
                new CDISDataToCSV($header, $mapped),
                $endpoint['ftp_path'].$cdisSync->branch_bid.'/'.$action.$endpoint['abv'].now()->format('mdy').'_'.$counter.'.csv',
                $disk);
        } else {
            return 'limit';
        }

        if ($converted == true) {
            EntryCounter::create([
                'mapping_type' => MappingType::CDIS_TO_POS,
                'counter' => $counter,
            ]);
            
            return true;
        } else {
            return false;
        }
    }

    /**
     * save file to FTP
     * 
     * @param  array   $headers
     * @param  array   $mapped
     * @param  object  $cdisSync
     * @param  array   $endpoint
     * @param  string  $action
     * @param  string  $disk
     * @param  array   $cdisData
     */
    public function saveToFTPGroup($headers, $mapped, $cdisSync, $endpoint, $action, $disk, $cdisData)
    {
        $folderCounter = FolderCounter::where(['mapping_type' => MappingType::CDIS_TO_POS, 'branch_bid' => $cdisSync->branch_bid])
            ->whereDate('created_at', DB::raw('CURDATE()'))
            ->orderBy('created_at', 'DESC')
            ->first();

        $fCount = $folderCounter ? $folderCounter->counter+1 : 1;

        if ($endpoint['name'] == ApiEndpoint::PRODUCT_STRUCTURE) {
            foreach ($headers as $key => $header) {
                $entryCounter = EntryCounter::where('mapping_type', MappingType::CDIS_TO_POS)
                    ->whereDate('created_at', DB::raw('CURDATE()'))
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $counter = $entryCounter ? $entryCounter->counter+1 : 1;

                if ($cdisData[$key]->table_name == 'product_structure') {
                    $prefix = "PSH_";
                } else {
                    $prefix = "PSD_";
                }

                $converted = Excel::store(
                    new CDISDataToCSV($header, [$mapped[$key]]),
                    $endpoint['ftp_path'].$cdisSync->branch_bid.'/'.$action.$endpoint['abv'].now()->format('mdy').'_'.$fCount.'/'.$prefix.now()->format('mdy').'_'.$counter.'.csv',
                    $disk);

                if ($converted == true) {
                    CDISSync::find($cdisData[$key]->bid)->delete();
                    EntryCounter::create([
                        'mapping_type' => MappingType::CDIS_TO_POS,
                        'counter' => $counter,
                    ]);
                }
            }
        } else if ($endpoint['name'] == ApiEndpoint::PRODUCT_STRUCTURE) {
            foreach ($headers as $key => $header) {
                $entryCounter = EntryCounter::where('mapping_type', MappingType::CDIS_TO_POS)
                    ->whereDate('created_at', DB::raw('CURDATE()'))
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $counter = $entryCounter ? $entryCounter->counter+1 : 1;

                if ($cdisData[$key]->table_name == 'branch_availability') {
                    $prefix = "BA_";
                } else if ($cdisData[$key]->table_name == 'branch_price') {
                    $prefix = "BP_";
                } else if ($cdisData[$key]->table_name == 'product_head') {
                    $prefix = "PH_";
                } else if ($cdisData[$key]->table_name == 'product_uom_packaging') {
                    $prefix = "PUP_";
                } else if ($cdisData[$key]->table_name == 'packaging_vendor') {
                    $prefix = "PV_";
                } else {
                    $prefix = "PVC_";
                }

                $converted = Excel::store(
                    new CDISDataToCSV($header, [$mapped[$key]]),
                    $endpoint['ftp_path'].$cdisSync->branch_bid.'/'.$action.$endpoint['abv'].now()->format('mdy').'_'.$fCount.'/'.$prefix.now()->format('mdy').'_'.$counter.'.csv',
                    $disk);

                if ($converted == true) {
                    CDISSync::find($cdisData[$key]->bid)->delete();
                    EntryCounter::create([
                        'mapping_type' => MappingType::CDIS_TO_POS,
                        'counter' => $counter,
                    ]);
                }
            }
        }

        if ($converted == true) {
            FolderCounter::create([
                'branch_bid' => $cdisSync->branch_bid,
                'mapping_type' => MappingType::CDIS_TO_POS,
                'counter' => $fCount,
            ]);
        }

    }
}