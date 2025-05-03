<?php

namespace App\Repositories\Eloquent;

use App\Entities\ItemAvailability;
use App\Enums\Status;
use App\Repositories\Contracts\CDISProductCategoryRepository;
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
                'bid',
                'product_uom_bid',
                'item_code',
                'barcode',
                'description',
                'long_description',
                'category_bid',
            ]);

        if (
            isset($filters->category)
            && (
                (is_array($filters->category) && count($filters->category) > 0)
                || (is_string($filters->category) && $filters->category != '')
            )
        ) {
            $categoryBids = $this->includeCategoryBids($filters->category);
            $this->model = $this->model->whereIn('item_availability.category_bid', $categoryBids);
        }

        if (isset($filters->search_keyword) && ! is_null($filters->search_keyword)) {
            $this->model = $this->model
                ->where(function($model) use ($filters) {
                    $model->where('barcode', 'LIKE', '%'.$filters->search_keyword.'%')
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

    public function includeCategoryBids($category)
    {
        $productCategory = app()->make(CDISProductCategoryRepository::class)->where('bid', $category)->first();

        if ($productCategory['level'] == 0) {
            $level1 = app()->make(CDISProductCategoryRepository::class)
                ->where('parent_bid', $category)
                ->where('level', 1);

            $level2 = app()->make(CDISProductCategoryRepository::class)
                ->whereIn('parent_bid', $level1->pluck('bid')->toArray())
                ->where('level', 2);

            return array_merge($category, $level1->pluck('bid')->toArray(), $level2->pluck('bid')->toArray());
        } else if ($productCategory['level'] == 1) {
            $level2 = app()->make(CDISProductCategoryRepository::class)
                ->where('parent_bid', $category)
                ->where('level', 2);

            return array_merge($category, $level2->pluck('bid')->toArray());
        } else {
            return (array) $category;
        }
    }


    /**
     * Get device item availability
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getDeviceAvailability($deviceSettingsBid)
    {
        $this->model =  DB::table('item_availability_detail')
            ->select([
                DB::raw('item_availability_detail.is_available as `is_available`'),
                DB::raw('item_availability.product_uom_bid as `product_uom_bid`'),
            ])
            ->leftJoin('item_availability', 'item_availability.bid', '=', 'item_availability_detail.head_bid')
            ->where('item_availability_detail.device_settings_bid', $deviceSettingsBid)
            ->groupBy(['item_availability.product_uom_bid']);

        $result = $this->model->get();
        $this->resetModel();

        return $result;
    }
}
