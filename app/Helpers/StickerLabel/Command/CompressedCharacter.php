<?php

namespace App\Helpers\StickerLabel\Command;

class CompressedCharacter implements CommandInterface
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param bool $enabled
     */
    public function __construct($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        return chr($this->enabled ? 15 : 18);
    }
}
