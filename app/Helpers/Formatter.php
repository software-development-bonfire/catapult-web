<?php

if (! function_exists('moneyToFloat')) {

    function moneyToFloat($value)
    {
        return floatval(str_replace(',' , '', $value));
    }
}
