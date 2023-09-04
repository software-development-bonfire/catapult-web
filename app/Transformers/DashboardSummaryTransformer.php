<?php

namespace App\Transformers;

use App\Entities\ErrorLog;
use App\Enums\DefinedDashboardCardList;
use League\Fractal\TransformerAbstract;

class DashboardSummaryTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(object $model)
    {
        $summaries = [];
        foreach (DefinedDashboardCardList::LIST as $summary) {
            $summary = (object) $summary;
            $slug = $summary->slug;
            if (isset($model->{$slug})) {
                $summary->count = $model->{$slug};
            }
            $summaries[] = $summary;
        }
        return $summaries;
    }
}
