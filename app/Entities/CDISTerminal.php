<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISTerminal extends BaseModel
{
    protected $table = 'cdis_terminal';

    protected $fillable = [
        'bid',
        'branch_bid',
        'number',
        'name',
        'status',
        'bir_serial_no',
        'ptu_no',
        'accreditation_no',
        'min',
        'sql_server',
        'sql_database',
        'sql_port',
        'sql_username',
        'sql_password',
        'product_license_key',
        'machine_uuid',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'number' => 'string',
    ];

    public function terminalTransactions()
    {
        return $this->hasMany(CDISTerminalTransaction::class, 'terminal_bid', 'bid');
    }

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid','bid');
    }

    public function product()
    {
        return $this->hasMany(CDISProduct::class, 'terminal_bid','bid');
    }
}
