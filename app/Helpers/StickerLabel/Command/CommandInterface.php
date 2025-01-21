<?php

namespace App\Helpers\StickerLabel\Command;

interface CommandInterface
{
    /**
     * The command to be processed.
     *
     * @return string
     */
    public function read();
}
