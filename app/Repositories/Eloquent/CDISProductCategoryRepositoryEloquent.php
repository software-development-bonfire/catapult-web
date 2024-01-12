<?php

namespace App\Repositories\Eloquent;

use App\Enums\Status;
use App\Entities\CDISProductCategory;
use App\Repositories\Contracts\CDISProductCategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CDISProductCategoryRepositoryEloquent extends BaseEloquent implements CDISProductCategoryRepository
{
    public function model()
    {
        return CDISProductCategory::class;
    }
}
