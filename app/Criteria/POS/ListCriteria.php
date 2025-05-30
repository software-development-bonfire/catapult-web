<?php

namespace App\Criteria\POS;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ListCriteria.
 *
 * @package namespace App\Criteria\Kitchen\StationProcess;
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
        $filters = (object) $this->filters;

        if (! empty($filters->transaction_id)) {
            $model->where('pos_terminal_transactions.transaction_id', $filters->transaction_id);
        }

        if (! empty($filters->or_number)) {
            $model->where('pos_terminal_transactions.or_number', $filters->or_number);
        }

        if (! empty($filters->terminal_bid)) {
            $model->where('pos_terminal_transactions.terminal_bid', $filters->terminal_bid);
        }

        if (! empty($filters->device_code)) {
            $model->where('pos_terminal_transactions.device_code', $filters->device_code);
        }

        if (! empty($filters->type)) {
            $model->where('pos_terminal_transactions.type', $filters->type);
        }

        if (! empty($filters->transaction_type)) {
            $model->where('pos_terminal_transactions.transaction_type', $filters->transaction_type);
        }

        if (! empty($filters->order_number)) {
            //$model->where('pos_terminal_transactions.order_number', $filters->order_number);
            $model->where('pos_terminal_transactions.order_number', 'LIKE', '%'.$filters->order_number.'%');
        }
        
        if (isset($filters->order_status) && $filters->order_status != '') {
            $model->where('pos_terminal_transactions.order_status', $filters->order_status);
        }
        
        return $model;
    }
}
