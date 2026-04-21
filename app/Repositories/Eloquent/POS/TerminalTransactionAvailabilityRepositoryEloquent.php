<?php

namespace App\Repositories\Eloquent\POS;

use App\Entities\CDISTerminalTransaction;
use App\Repositories\Contracts\POS\TerminalTransactionAvailabilityRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class TerminalTransactionAvailabilityRepositoryEloquent extends BaseRepository implements TerminalTransactionAvailabilityRepository
{
    public function model()
    {
        return CDISTerminalTransaction::class;
    }

    public function findByBidOrTransactionId($bid, $transactionId)
    {
        return $this->model
            ->when(!empty($bid), function ($query) use ($bid) {
                $query->where('bid', $bid);
            })
            ->when(empty($bid) && !empty($transactionId), function ($query) use ($transactionId) {
                $query->where('transaction_id', $transactionId);
            })
            ->first();
    }

    public function updateAvailabilityByBidOrTransactionId($bid, $transactionId, string $availability)
    {
        $transaction = $this->findByBidOrTransactionId($bid, $transactionId);

        if (!$transaction) {
            return null;
        }

        $transaction->availability = $availability;
        $transaction->save();

        return $transaction->fresh();
    }
}
