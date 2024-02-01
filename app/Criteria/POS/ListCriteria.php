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
        $filters = $this->filters;

        if (! empty($filters->transaction_bid)) {
            $model->where('pos_terminal_transactions.transaction_id', $filters->transaction_bid);
        }

        if (! empty($filters->terminal_bid)) {
            $model->where('pos_terminal_transactions.terminal_bid', $filters->terminal_bid);
        }

        if (! empty($filters->type)) {
            $model->where('pos_terminal_transactions.type', $filters->type);
        }

        if (! empty($filters->transaction_type)) {
            $model->where('pos_terminal_transactions.transaction_type', $filters->transaction_type);
        }

        return $model;
    }
}
