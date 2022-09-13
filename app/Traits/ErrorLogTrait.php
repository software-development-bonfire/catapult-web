<?php

namespace App\Traits;

use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Traits\FilenameRetryCounterTrait;

trait ErrorLogTrait
{
    use FilenameRetryCounterTrait;

    public function setErrorLog($entryLogLabel, $fileName, $path, $status, $detailSheet, $detailErrorType, $detailDescription)
    {
        $filename = $this->removeRetryCount($fileName);
        $errorLog = ErrorLog::where('filename', '=', $filename)->first();
        if ($errorLog === null) {
            $errorLog = ErrorLog::create(array(
                'pos_entry' => $entryLogLabel,
                'filename' => $filename,
                'path' => $path,
                'status' => $status,
            ));
        }

        ErrorLogDetail::create(array(
            'error_log_bid' => $errorLog->bid,
            'sheet' =>  isset($detailSheet) ? $detailSheet : '',
            'error_type' => $detailErrorType,
            'description' => $detailDescription,
        ));
    }

    public function createErrorLogFile($localDisk, $destinationErrorFolderPath, $status)
    {
        $errorLogs = ErrorLog::whereRaw('Date(created_at) = CURDATE()')->get();

        if ($errorLogs !== null) {
            if (count($errorLogs) > 0) {
                $filenames = Arr::pluck($errorLogs, 'filename');
                $entriesMaxLength = empty($filenames) ? 60 :  @max(array_map('strlen', $filenames)) ?? 60;
                $output = "";
                foreach ($errorLogs as $errorLog) {
                    $details = $errorLog->details;
                    $spaces = ($entriesMaxLength - strlen($errorLog->filename)) / 2;
                    $filenameLog = str_repeat(' ', ceil($spaces)) . $errorLog->filename . str_repeat(' ', floor($spaces));

                    if ($details !== null) {
                        foreach ($details as $detail) {
                            $output .= '[' . $errorLog->status . '][' . $detail->created_at . '][' . $errorLog->pos_entry . '][' . $filenameLog . '][' . $detail->error_type . ': ' . $detail->description . ']';
                            $output .= "\n";
                        }
                    } else {
                        $output .= '[' . $errorLog->status . '][' . $errorLog->created_at . '][' . $errorLog->pos_entry . '][' . $filenameLog . ']';
                    }
                }
                $today =  Carbon::now()->format('Y-m-d');
                $logFile = $destinationErrorFolderPath . '/catapult-' . $today . '.log';

                if ($localDisk->exists($logFile)) {
                    $localDisk->delete($logFile);
                }
                $localDisk->put($logFile, $output);
            }
        }
    }
}
