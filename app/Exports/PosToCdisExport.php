<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class PosToCdisExport implements WithEvents
{
    protected $data;

    public function __construct()
    {
        //
    }

    public function registerEvents(): array
    {
        return [
            
        ];
    }

}
