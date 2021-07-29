<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CDISDataToCSV implements WithEvents, WithStyles
{
    public function __construct($header, $value)
    {
        $this->header = $header;
        $this->value = $value;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $delegate = $event->sheet->getDelegate();

                $no_column = count($this->header)-1;

                $columnDimensions = array();

                $delegate->calculateColumnWidths();

                $row = 1;
                foreach(range('A','Z') as $key => $v){
                    if($key <= $no_column) {
                        $columnDimensions[] = [$v => ['setAutoSize' => true]];
                        $delegate->setCellValueExplicit($v.$row, $this->header[$key], DataType::TYPE_STRING);
                    }
                }

                $column = call_user_func_array("array_merge", $columnDimensions);

                foreach($column as $columnID => $properties) {
                    foreach($properties as $method => $value) {
                        $delegate->getColumnDimension($columnID)->$method($value);
                    }
                }

                foreach ($this->value as $value) {
                    $row++;
                    foreach(range('A','Z') as $key => $v){
                        if($key <= $no_column) {
                            if ($value[$key] == "" || $value[$key] == null) {
                                $delegate->setCellValue($v.$row, '""');
                            } else {
                                $delegate->setCellValueExplicit($v.$row, (string) $value[$key], DataType::TYPE_STRING);
                            }
                        }
                    }
                }
            }
        ];
    }
}
