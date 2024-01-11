<?php

namespace App\Repositories\Eloquent;

use App\Enums\Status;
use App\Entities\CDISProductCategory;
use App\Repositories\Contracts\CDISProductCategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CDISProductCategoryRepositoryEloquent extends BaseEloquent implements CDISProductCategoryRepository
{
    public function model()
    {
        return CDISProductCategory::class;
    }

    /**
     * Get product attribute list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function list($filters = null, $bid = null, $level, $isTablePaginate = false, $withInactive = true)
    {
        $this->model = $this->model
            ->select([
                DB::raw('cdis_product_category.bid as `bid`'),
                DB::raw('cdis_product_category.name as `name`'),
                DB::raw('cdis_product_category.button_color as `button_color`'),
                DB::raw('cdis_product_category.status as `status`'),
                DB::raw('cdis_product_category.parent_bid as `parent_bid`'),
                DB::raw('cdis_product_category.level as `level`'),
            ])
            ->where('cdis_product_category.level', '=', $level)
            ->orderBy('bid', 'ASC');

        if (! $withInactive) {
            $this->model->where('status', '<>', Status::INACTIVE);
        }

        if ($bid) {
            $this->model = $this->model
                ->where('cdis_product_category.parent_bid', '=', $bid);
        }

        if (
            isset($filters->parentBids)
            && (
                (is_array($filters->parentBids) && count($filters->parentBids) > 0)
                || (is_string($filters->parentBids) && $filters->parentBids != '')
            )
        ) {
            $this->model = $this->model->whereIn('cdis_product_category.parent_bid', $filters->parentBids);
        }

        $result = $isTablePaginate ? $this->paginate(app()->get('request')->get('itemsPerPage', 10)) : $this->get();

        return $result;
    }
}
