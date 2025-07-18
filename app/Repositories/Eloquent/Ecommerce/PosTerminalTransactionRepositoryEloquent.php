<?php

namespace App\Repositories\Eloquent\Ecommerce;

use App\Enums\Status;
use App\Criteria\BranchUniversalOrderSummary\ListCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Entities\POSTerminalTransaction;
use App\Entities\POSTerminalTransactionProduct;
use App\Repositories\Contracts\Ecommerce\PosTerminalTransactionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Enums\UsageType;

class PosTerminalTransactionRepositoryEloquent extends BaseRepository implements PosTerminalTransactionRepository
{
    public function model()
    {
        return POSTerminalTransaction::class;
    }

    public function list($data)
    {
        $data = (object) $data->transaction;
        
        $this->model = $this->model
            ->select([
                'pos_terminal_transactions.bid as bid',
                'pos_terminal_transactions.customer_name as name',
                'pos_terminal_transactions.customer_name as name',
                'pos_terminal_transactions.order_number',
                'pos_terminal_transactions.total_discount_amount as discount_amount',
                'pos_terminal_transactions.total_delivery_fee as delivery_fee',
                'pos_terminal_transactions.gross_sales as sub_total',
                'pos_terminal_transactions.total_tender as total_amount',
                'delivery_transaction.email_address as email_address'
            ])
        ->join('delivery_transaction', 'pos_terminal_transactions.bid', '=', 'delivery_transaction.pos_terminal_transaction_bid')
        ->where('order_number', $data->reference_number);

        return $this->model->get();
    }

    public function product($bid)
    {
        $this->model = POSTerminalTransactionProduct::select([
                'pos_terminal_transaction_products.bid',
                'pos_terminal_transaction_products.description as description',
                'pos_terminal_transaction_products.price as price',
            ])
        ->where('terminal_transaction_bid', $bid)
        ->where('usage_type', UsageType::PRODUCT);

        return $this->model->get();
    }

    public function AddOnModifier($bid)
    {
        
        $this->model = POSTerminalTransactionProduct::select([
                'pos_terminal_transaction_products.bid',
                'pos_terminal_transaction_products.description as description',
                'pos_terminal_transaction_products.price as price',
            ])
        ->where('parent_bid', $bid);

        return $this->model->get();
    }
}
