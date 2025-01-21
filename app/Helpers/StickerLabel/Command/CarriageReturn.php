<?php

namespace App\Helpers\StickerLabel\Command;

class CarriageReturn implements CommandInterface
{
    /**
     * @inheritdoc
     */
    public function read()
    {
        return chr(13);
    }
}
