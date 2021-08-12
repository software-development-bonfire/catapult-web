<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SystemLogExport implements FromArray
{
    public function __construct($header, $value)
    {
        $this->header = $header;
        $this->value = $value;
    }

    public function array(): array
    {
        return [$this->header, $this->value];
    }
}