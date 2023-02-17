<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenUser;
use App\Repositories\Contracts\CDISKitchenUserRepository;

class CDISKitchenUserRepositoryEloquent extends BaseEloquent implements CDISKitchenUserRepository
{
    public function model()
    {
        return CDISKitchenUser::class;
    }
}
