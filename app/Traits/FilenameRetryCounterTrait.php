<?php

namespace App\Traits;

use App\Observers\BidObserver;

/**
 * Trait FilenameRetryCounterTrait
 * @package App\Traits
 */
trait FilenameRetryCounterTrait
{
    /**
     * Check if filename pattern is valid
     * validation is not totally acurate
     * it checks only the count of splitted filename
     *
     * @param string  $fileName
     *
     * @return boolean
     */
    public function hasValidFilenamePattern($filename)
    {
        $explodedFilename = explode("_", $filename);
        return count($explodedFilename) > 6;
    }

    /**
     * This will add a (retry count) in filename as suffix
     *
     * @param string  $filename
     *
     * @return string constructed filename with (retry count)
     */
    public function setRetryCount($filename)
    {
        $extensions = explode('.', $filename);
        $extension = end($extensions);

        $explodedFilename = explode("_", $extensions[0]);
        $elementCount = count($explodedFilename);
        $lastText = $explodedFilename[$elementCount - 1];
        $retryCount = 0;

        preg_match('#\((.*?)\)#', $lastText, $match);
        if (isset($match[1])) {
            $retryCount = intval($match[1]) + 1;
            $explodedFilename[$elementCount - 1] = "({$retryCount})";
        } else {
            $explodedFilename[$elementCount] = "({$retryCount})";
        }

        return implode("_", $explodedFilename).".".$extension;
    }

    /**
     * Get (retry count) in filename
     *
     * @param string  $filename
     *
     * @return int $retryCount
     */
    public function getRetryCount($filename)
    {
        $retryCount = 0;
        preg_match('#\((.*?)\)#', $filename, $match);
        if (isset($match[1])) {
            $retryCount = $match[1];
        }
        return intval($retryCount);
    }

    /**
     * Remove (retry count) in filename
     * and returns to its original filename
     *
     * @param string  $filename
     *
     * @return int $originalFilename
     */
    public function removeRetryCount($filename)
    {
        $extension = null;
        $extensions = explode('.', $filename);
        if (count($extensions) > 1) {
            $extension = end($extensions);
        }        

        $explodedFilename = explode("_", $extensions[0]);
        $elementCount = count($explodedFilename);

        $lastText = $explodedFilename[$elementCount - 1];

        preg_match('#\((.*?)\)#', $lastText, $match);
        if (isset($match[1])) {
            unset($explodedFilename[$elementCount - 1]);
        }
        $extension= $extension !== null ? (".".$extension) : "";
        return implode("_", $explodedFilename).$extension;
    }
}
