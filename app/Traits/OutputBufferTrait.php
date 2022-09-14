<?php

namespace App\Traits;

/**
 * Trait OutputBufferTrait
 * @package App\Traits
 */
trait OutputBufferTrait
{
    /**
     * Flush internal output buffer in browser
     * 
     */
    public function flushOutputBuffer()
    {
        if (ob_get_length()) {
            ob_end_flush();
            flush();
        }
    }
}
