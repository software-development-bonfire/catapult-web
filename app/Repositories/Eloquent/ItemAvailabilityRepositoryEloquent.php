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
            $categoryBids = $this->includeCategoryBids($filters);
            $this->model = $this->model->whereIn('item_availability.category_bid', $categoryBids);
        }

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

    public function includeCategoryBids($filters)
    {
        if (
            isset($filters->sub_category_1)
            && (
                (is_array($filters->sub_category_1) && count($filters->sub_category_1) > 0)
                || (is_string($filters->sub_category_1) && $filters->sub_category_1 != '')
            )
        ) {
            if (
                isset($filters->sub_category_2)
                && (
                    (is_array($filters->sub_category_2) && count($filters->sub_category_2) > 0)
                    || (is_string($filters->sub_category_2) && $filters->sub_category_2 != '')
                )
            ) {
                $categories = [];

                $categoryLevel1 = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('bid', $filters->sub_category_1)
                    ->where('level', 1)
                    ->pluck('parent_bid')
                    ->toArray();

                foreach ($filters->category as $category) {
                    if (! in_array($category, $categoryLevel1)) {

                        $level1 = app()->make(CDISProductCategoryRepository::class)
                            ->where('parent_bid', $category)
                            ->where('level', 1);

                        $categories[] = $category;

                        if (is_array($level1) && count($level) > 0) {
                            $level1 = $level1->pluck('bid')->toArray();

                            $level2 = app()->make(CDISProductCategoryRepository::class)
                                ->whereIn('parent_bid', $level1)
                                ->where('level', 2);

                            $categories[] = $level1;
                            is_array($level2) && count($level2) > 0 ? $categories[] = $level2->pluck('bid')->toArray() : '';
                        }

                    }
                }

                $categoryLevel2 = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('parent_bid', $filters->sub_category_1)
                    ->where('level', 2)
                    ->pluck('bid')
                    ->toArray();

                foreach ($filters->sub_category_2 as $category2) {
                    if (in_array($category2, $categoryLevel2)) {
                        $categories[] = $category2;
                    }
                }

                $categoryLevel1 = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('parent_bid', $filters->sub_category_1)
                    ->where('level', 2)
                    ->pluck('bid')
                    ->toArray();

                foreach ($categoryLevel1 as $level1) {
                    if (! in_array($level1, $filters->sub_category_2)) {
                        $categories[] = $level1;
                    }
                }

                return $categories;
            } else {
                $categories = [];
                $level1 = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('parent_bid', $filters->category)
                    ->where('level', 1);

                $level2 = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('parent_bid', $filters->sub_category_1)
                    ->where('level', 2);

                $categoryLevel = app()->make(CDISProductCategoryRepository::class)
                    ->whereIn('bid', $filters->sub_category_1)
                    ->where('level', 1)
                    ->pluck('parent_bid')
                    ->toArray();

                foreach ($filters->category as $category) {
                    if (! in_array($category, $categoryLevel)) {

                        $level1 = app()->make(CDISProductCategoryRepository::class)
                            ->where('parent_bid', $category)
                            ->where('level', 1);

                        $categories[] = $category;

                        if (is_array($level1) && count($level) > 0) {
                            $level1 = $level1->pluck('bid')->toArray();

                            $level2 = app()->make(CDISProductCategoryRepository::class)
                                ->whereIn('parent_bid', $level1)
                                ->where('level', 2);

                            $categories[] = $level1;
                            is_array($level2) && count($level2) > 0 ? $categories[] = $level2->pluck('bid')->toArray() : '';
                        }

                    }
                }
                return array_merge($level1->pluck('bid')->toArray(), $level2->pluck('bid')->toArray(), $categories);
            }

        } else {

            $level1 = app()->make(CDISProductCategoryRepository::class)
                ->whereIn('parent_bid', $filters->category)
                ->where('level', 1);

            $level2 = app()->make(CDISProductCategoryRepository::class)
                ->whereIn('parent_bid', $level1->pluck('bid')->toArray())
                ->where('level', 2);

            return array_merge($filters->category, $level1->pluck('bid')->toArray(), $level2->pluck('bid')->toArray());
        }
    }
}
