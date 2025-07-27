<?php

namespace App\Criteria\POS;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ListDetailCriteria.
 *
 * @package namespace App\Criteria\POS\ListDetailCriteria;
 */
class ListDetailCriteria implements CriteriaInterface
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
        $filters = (object) $this->filters;

        if (! empty($filters->terminal_transaction_bid)) {
            $model->where('pos_terminal_transaction_products.terminal_transaction_bid', $filters->terminal_transaction_bid);
        }

        if (! empty($filters->usage_type)) {
            $model->where('pos_terminal_transaction_products.usage_type', $filters->usage_type);
        }

        if (! empty($filters->product_bid)) {
            $model->where('pos_terminal_transaction_products.product_bid', $filters->product_bid);
        }

        if (! empty($filters->parent_bid)) {
            $model->where('pos_terminal_transaction_products.parent_bid', $filters->parent_bid);
        }

        if (! empty($filters->cart_bid)) {
            $model->where('pos_terminal_transaction_products.cart_bid', $filters->cart_bid);
        }
        
        return $model;
    }
}
