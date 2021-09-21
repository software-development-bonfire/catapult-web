<?php

namespace App\Repositories\Eloquent;

use App\Criteria\ProductVariant\ListCriteria as ProductVariantListCriteria;
use App\Entities\CDISProductVariant;
use App\Repositories\Contracts\CDISProductVariantRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CDISProductVariantRepositoryEloquent extends BaseEloquent implements CDISProductVariantRepository
{
    public function model()
    {
        return CDISProductVariant::class;
    }

    /**
     * Get product variant option list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function variantAndOptionList($filters)
    {
        $this->model = $this->model
            ->select([
                DB::raw('cdis_product_variant.bid as attribute_bid'),
                DB::raw('cdis_product_variant.description as attribute_name'),
                DB::raw('cdis_product_variant_option.bid as option_bid'),
                DB::raw('cdis_product_variant_option.name as option_name'),
            ])
            ->join(
                'cdis_product_variant_option',
                'cdis_product_variant_option.head_bid',
                '=',
                'cdis_product_variant.bid');

        $this->pushCriteria(new ProductVariantListCriteria($filters))->applyCriteria();

        return $this->model->get();
    }
}
