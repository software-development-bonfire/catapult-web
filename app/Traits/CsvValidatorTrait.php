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
    public static $EXPECTED_MIN_HEADERS_COUNT = 3;

    /**
     * Determine if the file having extension .CSV
     *
     * @param  string  $file
     * @return bool
     */
    public function isCsvFile($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (empty($extension) || $extension != 'csv') {
            return false;
        }
        return true;
    }

    /**
     * Determine if the CSV file is valid
     *
     * @param  string  $content
     * @return bool
     */
    public function isValidCsv($content)
    {
        $valid = false;
        if ($this->isCorruptedContent($content) == false) {
            $headers = $this->parseHeadersOnly($content);
            // Get valid headers then compare the minimum expected 
            // headers count to make sure it contains valid CSV
            if (count($headers) > CsvValidatorTrait::$EXPECTED_MIN_HEADERS_COUNT) {
                // Parse the content into an array object
                $contentArrayObject = $this->parseToArrayObject($content);
                // @TODO: Need to validate if the rows has valid data
                $valid = true;
            }
        }
        return $valid;
    }

    /**
     * Check if the content of the CSV is not corrupted
     *
     * @param  string  $content
     * @return bool
     */
    public function isCorruptedContent($content)
    {
        $isCorrupted = false;
        // Validate if the content is contains unprintable chars
        // or non-ascii chars
        if (preg_match_all('/\\u0000/', $content, $matches)) {
            if (count($matches) > 0) {
                // If contains non-ascii chars then we need to eliminate
                // by replacing it with empty chars then re-validate
                // the cleaned content if empty, means the content is corrupted
                $cleanedContent = preg_replace('/\\u0000/', '', $content);
                $isCorrupted = empty(trim($cleanedContent));
            }
        }
        return $isCorrupted;
    }

    /**
     * Parse CSV content and returns on the column headers
     *
     * @param  string  $content
     * @return mixed
     */
    public function parseHeadersOnly($content)
    {
        $rows = array_map('str_getcsv', explode(PHP_EOL, $content));
        //Shifts the first value of the array off and returns it
        // to get the column headers
        $keys = array_shift($rows);

        // We need to clean the returned column headers
        // to make sure it does not contains non-ascii chars
        array_walk_recursive(
            $keys,
            function (&$value) {
                $value = trim(preg_replace('/\\u0000/', '', $value));
            }
        );
        // After cleaning, we need to remove empty elements in the array
        // to make sure empty headers will be avoided
        return array_filter($keys);
    }

    /**
     * Parse CSV content into array object
     *
     * @param  string  $content
     * @return mixed
     */
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
