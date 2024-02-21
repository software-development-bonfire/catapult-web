<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISCustomer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'first_name',
        'middle_name',
        'last_name',
        'suffix_name',
        'address',
        'contact_number',
        'email_address',
        'gender',
        'birthday',
        'type',
        'created_from',
        'business_style',
        'tin',
        'payment_term_settings_bid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'payment_term_settings_bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function paymentTerm()
    {
        return $this->belongsTo(CDISPaymentTermSettings::class, 'payment_term_settings_bid', 'bid')->withDefault(['name' => '']);
    }
}
