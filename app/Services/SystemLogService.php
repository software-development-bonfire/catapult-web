<?php

namespace App\Services;

use App\Entities\SystemLog;
use Illuminate\Support\Facades\Auth;

class SystemLogService
{
    /**
     * Create system log resources
     *
     * @param  bool  $initiator
     * @param  string  $process
     * @param  string  $action
     * @param  string  $description
     * 
     */
    public function log($initiator = false, $process, $action, $description)
    {
        $initiator = $initiator ? Auth::user()->username : 'system';
        SystemLog::create([
            'initiator' => $initiator,
            'module_process' => $process,
            'action' => $action,
            'description' => $description,
        ]);
    }
}


