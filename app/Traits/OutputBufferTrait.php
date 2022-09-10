<?php

namespace App\Traits;

trait OutputBufferTrait
{
    public function flushOutputBuffer()
    {
        if (ob_get_length()) {
            ob_end_flush();
            flush();
        }
    }
}
