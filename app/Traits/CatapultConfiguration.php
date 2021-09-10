<?php

namespace App\Traits;

use App\Entities\Configuration;

trait CatapultConfiguration
{
    use DatabaseTransaction;

    public function storeAttribute($data)
    {
        $that = $this;

        function storeAttribute($datum)
        {
            $configuration = Configuration::create([
                'attribute' => $datum->attribute,
                'value' => $datum->value,
            ]);

            return $configuration;
        }

        return $this->transaction(function() use($that, $data) {
            if (array_key_exists('0', $data)) {
                foreach ($data as $datum) {
                    $datum = (object) $datum;

                    storeAttribute($datum);
                }
            } else {
                $datum = (object) $data;

                storeAttribute($datum);
            }
        });
    }

    public function updateAttribute($data)
    {
        $that = $this;

        function updateAttribute($datum)
        {
            $attribute = Configuration::query()->where('attribute', $datum->attribute);

            $attribute->update([
                'value' => $datum->value,
            ]);

            return $attribute;
        }

        return $this->transaction(function() use($that, $data) {
            if (array_key_exists('0', $data)) {
                foreach ($data as $datum) {
                    $datum = (object) $datum;

                    updateAttribute($datum);
                }
            } else {
                $datum = (object) $data;

                updateAttribute($datum);
            }
        });
    }

    public function isAttributeExists($attribute)
    {
        return Configuration::query()
            ->where('attribute', $attribute)
            ->where('value', '<>', '')
            ->exists();
    }

    public function getAttributeValue($attribute)
    {
        $value = Configuration::query()->where('attribute', $attribute)->pluck('value');

        return count($value) > 0 ? $value[0] : null;
    }
}
