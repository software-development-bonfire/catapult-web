<?php

namespace App\Criteria\BranchUniversalOrderSummary;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class ListCriteria.
 *
 * @package namespace App\Criteria\BranchUniversalOrderSummary;
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

        if (isset($filters->search_keyword) && $filters->search_keyword != '')
        {

            $model->where(function($model) use ($filters) {
                $model->where('pos_terminal_transactions.or_number', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.log_date', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.order_schedule', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.device_type', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.order_number', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.type', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.payment_status', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('cdis_terminal.name', 'LIKE', '%'.$filters->search_keyword.'%')
                    ->orWhere('pos_terminal_transactions.status', 'LIKE', '%'.$filters->search_keyword.'%');
            });
        }

        return $model;
    }
}
