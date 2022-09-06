<?php

namespace App\Entities;

class CDISPaymentMethodSettingsDetail extends BaseModel
{
    public $timestamps = false;

    protected $table = 'cdis_payment_method_settings_detail';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'field_name',
        'field_value',
        'is_required',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'is_required' => 'boolean'
    ];

    public function paymentMethodSettings()
    {
        return $this->belongsTo(CDISPaymentMethodSettings::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        $headTableName = 'cdis_payment_method_settings';
        $headBid = null;

        if ($this->head !== null) {
            $headFirstData =  $this->paymentMethodSettings()->first();
            if ($headFirstData !== null) {
                $headTableName = $headFirstData->getTable();
                $headBid = $headFirstData->bid;
            }
        }

        $code = '';
        $level = 2;

        return (object) array(
            'code' => $code,
            'group' => $headTableName,
            'head_bid' => $headBid,
            'level' => $level,
        );
    }
}
