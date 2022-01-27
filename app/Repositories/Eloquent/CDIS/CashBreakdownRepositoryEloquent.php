<?php

namespace App\Repositories\Eloquent\CDIS;

use App\Entities\CDISCashBreakdown;
use App\Repositories\Contracts\CDIS\CashBreakdownRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class CashBreakdownRepositoryEloquent extends BaseRepository implements CashBreakdownRepository
{
    public function model()
    {
        return CDISCashBreakdown::class;
    }
}
