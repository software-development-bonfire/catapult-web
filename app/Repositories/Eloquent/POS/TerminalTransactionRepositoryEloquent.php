<?php

namespace App\Repositories\Eloquent\POS;

use App\Criteria\POS\ListCriteria as TerminalTransactionListCriteria;
use App\Entities\POSTerminalTransaction;
use App\Entities\POSTerminalTransactionProduct;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class TerminalTransactionRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class TerminalTransactionRepositoryEloquent extends BaseRepository implements TerminalTransactionRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return POSTerminalTransaction::class;
    }

    public function list($filters = [])
    {
        $this->model = $this->model->with(['details', 'payments'])->orderBy('bid', 'ASC');

        $this->pushCriteria(new TerminalTransactionListCriteria($filters))->applyCriteria();

        return $this->model->get();
    }

    public function details($filters = null)
    {
        $model = POSTerminalTransactionProduct::where(function ($query) use ($filters) {
            if (isset($filters)) {
                if (! empty($filters->transaction_bid)) {
                    $query->where('pos_terminal_transaction_products.terminal_transaction_bid', $filters->transaction_bid);
                }

                if (isset($filters->cart_bid) && isValidStringOrArray($filters->cart_bid)) {
                    $query->whereIn('pos_terminal_transaction_products.cart_bid',  toSafeArray($filters->cart_bid));
                }
            }
        });

        return $model->get();
    }
}
