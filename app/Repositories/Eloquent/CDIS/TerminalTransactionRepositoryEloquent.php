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
}
