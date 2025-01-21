<?php

namespace App\Helpers\StickerLabel\Command;

class LineFeed implements CommandInterface
{
    /**
     * @inheritdoc
     */
    public function read()
    {
        return chr(10);
    }
}
