<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function getTableColumns() {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
}
