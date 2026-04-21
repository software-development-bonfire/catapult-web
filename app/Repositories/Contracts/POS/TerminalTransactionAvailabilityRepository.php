<?php

namespace App\Repositories\Contracts\POS;

use Prettus\Repository\Contracts\RepositoryInterface;

interface TerminalTransactionAvailabilityRepository extends RepositoryInterface
{
    public function findByBidOrTransactionId($bid, $transactionId);

    public function updateAvailabilityByBidOrTransactionId($bid, $transactionId, string $availability);
}
