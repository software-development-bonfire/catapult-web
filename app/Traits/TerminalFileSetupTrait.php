<?php

namespace App\Traits;

use App\Enums\ReportFileType;
use Illuminate\Support\Carbon;

/**
 * Trait TerminalFileSetupTrait
 * @package App\Traits
 */
trait TerminalFileSetupTrait
{
    /**
     * Get folder sub-directory from different pattern
     * if not null and configured accordingly
     * 
     * @param TerminalFileSetup $terminalFileSetup
     * @return String $subFolder
     */
    public function getSubFolder($terminalFileSetup)
    {
        $subFolder = false;

        if (empty($terminalFileSetup->sub_directories)) {
            //@TODO: For future use
        } else {
            $value = $terminalFileSetup->sub_directories;
            $explodedValue = explode('_SEPARATOR_', $value);
            if (count($explodedValue) > 1) {
                if (\Illuminate\Support\Str::start($explodedValue[0], 'dir')) {
                    // Extract value inside the <dir> start-end separator
                    $directoryPattern = preg_replace("/dir#(.*)#dir/", '$1', $explodedValue[0]);
                    $subFolder = $this->extractValue($directoryPattern);
                }
                if (\Illuminate\Support\Str::start($explodedValue[1], 'file')) {
                    //@TODO
                }
            } else {
                if (\Illuminate\Support\Str::start($explodedValue[0], 'dir')) {
                    // Extract value inside the <dir> start-end separator
                    $directoryPattern = preg_replace("/dir#(.*)#dir/", '$1', $explodedValue[0]);
                    $subFolder = $this->extractValue($directoryPattern);
                }

                if (\Illuminate\Support\Str::start($explodedValue[0], 'file')) {
                    //@TODO
                }
            }
        }
        return $subFolder;
    }

    /**
     * Get the value inside defined pattern
     * of separator either <dir> or <file>
     * 
     * @param String $pattern
     * @return String $value
     */
    private function extractValue($pattern)
    {
        $value = '';

        // Check if sub-directory value is configured to <eval>
        // we need to extract the value inside the eval start-end
        // separator then execute value in eval function
        if (\Illuminate\Support\Str::contains($pattern, ['eval#', '#eval'])) {
            $extractedPattern = preg_replace("/eval#(.*)#eval/", '$1', $pattern);
            eval($extractedPattern);
        }

        // Check if sub-directory value is configured to <regex>
        // we need to extract the value inside the regex start-end
        // separator then perform replace
        if (\Illuminate\Support\Str::contains($pattern, ['regex#', '#regex'])) {
            $extractedPattern = preg_replace("/regex#(.*)#regex/", '$1', $pattern);
        }
        return $value;
    }

    /**
     * Extract log date value in filename or in
     * file content, using regex and date formatting
     * 
     * @param String $file
     * @param String $content
     * @param ReportFileType $reportFileType
     * @return String $date
     */
    private function getDate($file, $content, $reportFileType)
    {
        $date = Carbon::now();

        if ($reportFileType === ReportFileType::Z_READING || $reportFileType === ReportFileType::SALES_TRANSACTIONS) {
            // If the file is z-reading or receipts of a transaction, we need to get the log date
            // inside the file content using defined patterns; Tested only in generated receipts
            // of 1TEQ POS System.
            $pattern = "/Log Date.*: (.*)/";
            if ($reportFileType === ReportFileType::SALES_TRANSACTIONS) {
                $pattern = "/(LOGDATE.*):(.*)/";
            }

            if (preg_match_all($pattern, $content, $matches)) {
                $match = implode(",", $matches[0]);
                if (! empty($match)) {
                    $chunks =  explode(":", $match);
                    if (! empty($chunks) && count($chunks) > 1) {
                        $date = trim($chunks[1]);
                        if (! empty($date)) {
                            $date = date_create_from_format("m/d/Y", $date);
                            if (! empty($date)) {
                                $date = date_format($date, "Y-m-d");
                            }
                        }
                    }
                }
            }
        } else if ($reportFileType === ReportFileType::JOURNAL_REPORTS) {
            // If the file journal report, then we need to extract date
            // from its filename (we assume that the file is in mdY format),
            // Other than that, it needs to be revised; 
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $createdDate = date_create_from_format("mdY", $filename);
            if (! empty($createdDate)) {
                $date = date_format($createdDate, "Y-m-d");
            }
        } else {
        }
        return $date;
    }
}
