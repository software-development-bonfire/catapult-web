<?php

namespace App\Traits;

use App\Enums\DisplayState;
use Carbon\Carbon;
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
}
