<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Trait CsvValidatorTrait
 * @package App\Traits
 */
trait CsvValidatorTrait
{
    public function isCsvFile($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (empty($extension) || $extension != 'csv') {
            return false;
        }
        return true;
    }

    public function isValidCsv($content)
    {
        $valid = false;
        if ($this->isCorruptedContent($content) == false) {
            $headers = $this->parseHeadersOnly($content);
            if (count($headers) > 1) {
                $contentArray = $this->parseToArrayObject($content);
                $valid = true;
            }
        }
        return $valid;
    }

    public function isCorruptedContent($content)
    {
        $isCorrupted = false;
        if (preg_match_all('/\\u0000/', $content, $matches)) {
            if (count($matches) > 0) {
                $cleanedContent = preg_replace('/\\u0000/', '', $content);
                $isCorrupted = empty(trim($cleanedContent));
            }
        }
        return $isCorrupted;
    }

    public function parseHeadersOnly($content)
    {
        $rows = array_map('str_getcsv', explode(PHP_EOL, $content));
        $keys = array_shift($rows);
        array_walk_recursive(
            $keys,
            function (&$v) {
                $v = trim(preg_replace('/\\u0000/', '', $v));
            }
        );
        return array_filter($keys);
    }

    public function parseToArrayObject($content)
    {
        $rows = array_map('str_getcsv', explode(PHP_EOL, $content));
        $rowKeys = array_shift($rows);
        $formattedData = [];
        foreach ($rows as $row) {
            if (sizeof($row) == sizeof($rowKeys)) {
                $associatedRowData = array_combine($rowKeys, $row);
                if (empty($keyField)) {
                    $formattedData[] = $associatedRowData;
                } else {
                    $formattedData[$associatedRowData[$keyField]] = $associatedRowData;
                }
            }
        }
        return $formattedData;
    }
}
