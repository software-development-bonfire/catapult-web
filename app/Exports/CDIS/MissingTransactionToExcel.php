<?php

namespace App\Exports\CDIS;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class MissingTransactionToExcel implements WithEvents, ShouldAutoSize
{
    public $header = [];
    public $value = [];
    public $extension = '.csv';

    public function __construct($header, $value, $extension)
    {
        $this->header = $header;
        $this->value = $value;
        $this->extension = $extension;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $delimiter = config('excel.exports.csv.delimiter');

                $delegate = $event->sheet->getDelegate();

                $row = 1;

                if (count($this->header)) {
                    foreach ($this->header as $index => $header) {
                        $columnIndex = $index + 1;
    
                        if (! ($header == "" || $header == null)) {
                            $delegate->setCellValueExplicitByColumnAndRow($columnIndex, $row, $header, DataType::TYPE_STRING);
                        }
                    }
    
                    $row++;
                }

                $isNotMultidimensionalArray =
                    count($this->value) == count($this->value, COUNT_RECURSIVE);

                if ($isNotMultidimensionalArray) {
                    foreach ($this->value as $index => $value) {
                        $columnIndex = $index + 1;

                        if (strpos($value, $delimiter) !== false) {
                            $value = '"'.$value.'"';
                        }

                        $delegate->setCellValueByColumnAndRow(
                            $columnIndex,
                            $row,
                            $value);
                    }
                } else {
                    foreach ($this->value as $index => $entry) {
                        foreach ($entry as $entryIndex => $value) {
                            $columnIndex = $entryIndex + 1;

                            if (strpos($value, $delimiter) !== false) {
                                $value = '"'.$value.'"';
                                $delegate->setCellValueExplicitByColumnAndRow(
                                    $columnIndex,
                                    $row,
                                    $value,
                                    DataType::TYPE_STRING);
                            } else {
                                $delegate->setCellValueByColumnAndRow(
                                    $columnIndex,
                                    $row,
                                    $value);
                            }
                        }

                        $row++;
                    }
                }
            }
        ];
    }
}
