<?php

namespace App\Repositories\Eloquent\CDIS;

use App\Entities\CDISBranch;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Eloquent\BaseEloquent;

class BranchRepositoryEloquent extends BaseEloquent implements BranchRepository
{
    public function model()
    {
        return CDISBranch::class;
    }
}
