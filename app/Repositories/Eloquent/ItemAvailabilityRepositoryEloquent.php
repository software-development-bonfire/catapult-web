<?php

namespace App\Repositories\Eloquent;

use App\Entities\ItemAvailability;
use App\Enums\Status;
use App\Repositories\Contracts\ItemAvailabilityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Eloquent\BaseRepository;

class ItemAvailabilityRepositoryEloquent extends BaseRepository implements ItemAvailabilityRepository
{
    public function model()
    {
        return ItemAvailability::class;
    }

    /**
     * Get list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function list($filters, $paginate = true)
    {
        $this->model = $this->model->select([
                'item_availability.bid',
                'item_availability.product_uom_bid',
                'item_availability.item_code',
                'item_availability.barcode',
                'item_availability.description',
                'item_availability.long_description',
            ]);

        if (isset($filters->search_keyword) && ! is_null($filters->search_keyword)) {
            $this->model = $this->model
                ->where(function($model) use ($filters) {
                    $model->where('item_code', 'LIKE', '%'.$filters->search_keyword.'%')
                        ->orWhere('barcode', 'LIKE', '%'.$filters->search_keyword.'%')
                        ->orWhere('description', 'LIKE', '%'.$filters->search_keyword.'%')
                        ->orWhere('long_description', 'LIKE', '%'.$filters->search_keyword.'%')
                    ;
                });
        }

        if ($paginate) {
            return $this->paginate(app()->get('request')->get('itemsPerPage', 10));
        }
        return $this->model->get();
    }
}
