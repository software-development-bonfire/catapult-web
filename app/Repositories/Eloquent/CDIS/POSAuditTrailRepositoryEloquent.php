<?php

namespace App\Repositories\Eloquent\CDIS;

use App\Entities\CDISPOSAuditTrail;
use App\Repositories\Contracts\CDIS\POSAuditTrailRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class POSAuditTrailRepositoryEloquent extends BaseRepository implements POSAuditTrailRepository
{
    public function model()
    {
        return CDISPOSAuditTrail::class;
    }
}
