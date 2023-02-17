<?php

namespace App\Criteria\ProductVariant;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ListCriteria.
 *
 * @package namespace App\Criteria\ProductVariant;
 */
class ListCriteria implements CriteriaInterface
{
    /**
     * @var array|mixed
     */
    protected $filters;

    /**
     * @param array|mixed $filters
     */
    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Apply criteria in query repository
     *
     * @param object              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $filters = $this->filters;

        if (
            isset($filters->product_variant_option_bid)
            && (
                (is_array($filters->product_variant_option_bid) && count($filters->product_variant_option_bid) > 0)
                || (is_string($filters->product_variant_option_bid) && $filters->product_variant_option_bid != '')
            )
        ) {

            $model->whereIn('cdis_product_variant_option.bid',
                is_array($filters->product_variant_option_bid) ? $filters->product_variant_option_bid : array($filters->product_variant_option_bid) );
        }

        return $model;
    }
}
