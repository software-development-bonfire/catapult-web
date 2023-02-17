<?php

namespace App\Services;

use App\Entities\Configuration;

class ConfigurationService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        Configuration::find('syncing_order')->update(['value' => $data['syncing_order']]);
        Configuration::find('pos_to_cdis_entry_limit')->update(['value' => $data['pos_to_cdis_entry_limit']]);        
        Configuration::find('cdis_to_pos_entry_limit')->update(['value' => $data['cdis_to_pos_entry_limit']]);        
    }

    public function getAttributeValue($attribute)
    {
        return Configuration::query()->where('attribute', $attribute)->pluck('value')[0];
    }
}
