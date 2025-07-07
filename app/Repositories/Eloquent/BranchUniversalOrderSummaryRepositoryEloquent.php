<?php

namespace App\Repositories\Eloquent;

use App\Enums\Status;
use App\Criteria\BranchUniversalOrderSummary\ListCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Entities\POSTerminalTransaction;
use App\Repositories\Contracts\BranchUniversalOrderSummaryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchUniversalOrderSummaryRepositoryEloquent extends BaseRepository implements BranchUniversalOrderSummaryRepository
{
    public function model()
    {
        return POSTerminalTransaction::class;
    }

    public function list($filters, $sort)
    {
        $this->model = $this->model
            ->select([
                'pos_terminal_transactions.bid',
                'pos_terminal_transactions.or_number',
                'pos_terminal_transactions.log_date',
                'pos_terminal_transactions.order_schedule',
                'pos_terminal_transactions.device_type',
                'pos_terminal_transactions.order_number',
                'pos_terminal_transactions.type',
                'pos_terminal_transactions.order_number',
                'pos_terminal_transactions.payment_status',
                'pos_terminal_transactions.status',
                'cdis_terminal.name as terminal_name'
            ])
            // ->leftJoin('pos_terminal_transaction_products', 'pos_terminal_transaction_products.terminal_transaction_bid', 'pos_terminal_transactions.bid')
            ->leftJoin('cdis_terminal', 'cdis_terminal.bid', 'pos_terminal_transactions.terminal_bid')
            ->leftJoin('cdis_customer', 'cdis_customer.bid', 'pos_terminal_transactions.customer_bid')
            ;

        if (isset($sort->current)) {
            $this->model = $this->model->orderBy($sort->current, $sort->order);
        }

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();
        return $this->paginate(25);
    }
}
