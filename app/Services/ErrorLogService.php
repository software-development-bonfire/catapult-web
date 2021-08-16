<?php

namespace App\Services;

use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Enums\Disk;
use Illuminate\Support\Facades\Storage;

class ErrorLogService
{
    /**
     * Create the specified resource in storage.
     *
     * @param array  $data
     */
    public function store($data)
    {
        $errorExist = ErrorLog::where('filename', $data['filename'])->first();
        if (! $errorExist) {
            $error_log = ErrorLog::create([
                'pos_entry' => $data['endpoint'],
                'filename' => $data['filename'],
                'status' => $data['status']
            ]);
    
            ErrorLogDetail::create([
                'error_log_bid' => $error_log->bid,
                'sheet' => $data['sheet'],
                'error_type' => $data['error_type'],
                'description' => $data['description']
            ]);
        }
    }

    /**
     * Move the file to Failed conversion
     *
     * @param string  $directory
     * @param array  $endpoint
     */
    public function moveFailedConversion($directory, $endpoint)
    {
        resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
            app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path());
            
        Storage::disk(Disk::LOCAL_POS_TO_CDIS)
            ->move($endpoint['source_path'].'/'.$directory, 
            $endpoint['failed'].'/'.$directory);
    }
}


