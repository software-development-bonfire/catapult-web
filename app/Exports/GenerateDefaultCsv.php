<?php

namespace App\Exports;

use App\Entities\FieldMappingList;
use App\Enums\Acronym;
use App\Enums\Status;
use App\User;
use Maatwebsite\Excel\Concerns\FromArray;

class GenerateDefaultCsv implements FromArray
{
    public function __construct($array)
    {
        $this->array = $array;
    }

    public function array(): array
    {
        $header = [];
        $default = [];
        foreach($this->array as $data) {
            $header[] = $data->field;
            $default[] = $data->default_value;
        }
        return [$header, $default];
    }
}
