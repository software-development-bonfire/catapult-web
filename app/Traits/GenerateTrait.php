<?php

namespace App\Traits;

use App\Enums\DisplayState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait GenerateTrait
{
    public function setGenerated($model, $bids, $isGenerated = DisplayState::YES)
    {
        $data = [
            'is_generated' => $isGenerated,
            'generated_at' => Carbon::now(),
        ];
        $model = $model->whereIn('bid', is_array($bids) ? $bids : array($bids));
        if ($model) {
            $model->update($data);
        }
    }

    public function setBranchGenerated()
    {
        Storage::disk('public')->put('CSV', 'This file means Create CSV for New Branch already executed.');
    }

    public function isBranchGenerated()
    {
        $exist = false;
        if (File::exists(public_path('CSV'))) {
            $exist = true;
        }
        return $exist; // (Storage::disk('public')->exists('CSV'));
    }
}
