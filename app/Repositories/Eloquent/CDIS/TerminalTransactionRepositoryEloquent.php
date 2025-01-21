<?php

namespace App\Repositories\Eloquent\CDIS;


use App\Entities\CDISTerminalTransaction;
use App\Repositories\Contracts\CDIS\TerminalTransactionRepository;
use App\Repositories\Eloquent\BaseEloquent;

class TerminalTransactionRepositoryEloquent extends BaseEloquent implements TerminalTransactionRepository
{
    public function model()
    {
        return CDISTerminalTransaction::class;
    }

    public function getTransaction($filters = null)
    {
        $this->model = $this->model
            ->with(['details'])
            ->where(function ($query) use ($filters) {
                if (isset($filters) && isset($filters->transaction)) {
                    $transaction = (object) stringToJson($filters->transaction);
                    if (! empty($transaction->bid)) {
                        $query->where('bid', $transaction->bid);
                    }
                    if (! empty($transaction->transaction_id)) {
                        $query->where('transaction_id', $transaction->transaction_id);
                    }
                }
            })
            ->orderBy('bid', 'ASC');
    }
}
