<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait StorageTrait
{
    public function checkDirectory($rootFolder, $targetFoler)
    {
        $checked = true;
        try {
            $remoteDisk = Storage::disk($rootFolder);
            if (!$remoteDisk->exists($targetFoler)) {
                $remoteDisk->makeDirectory($targetFoler);
            }
        } catch (\Exception $ex) {
            $checked = false;
        }
        return $checked;
    }
}
