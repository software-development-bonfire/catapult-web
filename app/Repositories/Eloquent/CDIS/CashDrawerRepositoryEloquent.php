<?php

namespace App\Repositories\Eloquent\CDIS;

use App\Entities\CDISCashDrawer;
use App\Repositories\Contracts\CDIS\CashDrawerRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class CashDrawerRepositoryEloquent extends BaseRepository implements CashDrawerRepository
{
    public function model()
    {
        return CDISCashDrawer::class;
    }
}
