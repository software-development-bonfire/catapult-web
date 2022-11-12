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

if (! function_exists('genericGroupBy')) {
    function genericGroupBy($array, $key) {
        $return = array();
        
        foreach($array as $val) {
            $return[$val->$key][] = $val; 
        //  $return[$val[$key]][] = $val; 
        }
        return $return;
    }
}

if (!function_exists('urlToDomain')) {
    function urlToDomain($url)
    {
        $input = trim($url, '/');
        if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }
        $urlParts = parse_url($input);
        $domain = preg_replace('/^www\./', '', $urlParts['host']);

        return $domain;
    }
}


