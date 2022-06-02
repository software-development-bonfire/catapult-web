<?php

if (! function_exists('moneyToFloat')) {

    function moneyToFloat($value)
    {
        return floatval(str_replace(',' , '', $value));
    }
}

if (! function_exists('toSafeValue')) {

    function toSafeValue($value, $defaultValue)
    {
        return isset($value) ? $value : $defaultValue;
    }
}
