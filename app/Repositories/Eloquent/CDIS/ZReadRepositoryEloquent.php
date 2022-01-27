<?php

namespace App\Repositories\Eloquent\CDIS;

use App\Entities\CDISZread;
use App\Repositories\Contracts\CDIS\ZReadRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class ZReadRepositoryEloquent extends BaseRepository implements ZReadRepository
{
    public function model()
    {
        return CDISZread::class;
    }
}
