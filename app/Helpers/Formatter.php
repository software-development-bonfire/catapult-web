<?php

use Illuminate\Support\Carbon;

if (! function_exists('moneyToFloat')) {

    function moneyToFloat($value)
    {
        return floatval(str_replace(',' , '', $value));
    }
}


if (! function_exists('floatToMoney')) {

    function floatToMoney($value, $currencyDecimal = 2, $withThousandSeparator = true)
    {
        $thousandSeparator = $withThousandSeparator ? ',' : '';
        return number_format(moneyToFloat($value), $currencyDecimal, '.', $thousandSeparator);
    }
}

if (! function_exists('floatToNullMoney')) {

    function floatToNullMoney($value, $currencyDecimal = 2, $defaultValue = '', $withThousandSeparator = true)
    {
        $thousandSeparator = $withThousandSeparator ? ',' : '';
        $numericValue = moneyToFloat($value);
        if ($numericValue <> 0) {
            return number_format($numericValue, $currencyDecimal, '.', $thousandSeparator);
        }
        return $defaultValue;
    }
}

if (! function_exists('toSafeValue')) {

    function toSafeValue($value, $defaultValue)
    {
        return isset($value) ? $value : $defaultValue;
    }
}

if (! function_exists('starts_with_toupper')) {

    function starts_with_toupper($haystack, $needles)
    {
        return str_starts_with(strtoupper($haystack), $needles);
    }
}

if (! function_exists('toBooleanOrInt')) {
    function toBooleanOrInt($value, $defaultValue)
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $defaultValue
            : ((int) $value
                ? filter_var($value, FILTER_VALIDATE_INT)
                : false
            );

        return $value;
    }
}


if (! function_exists('toSafeBoolean')) {
    function toSafeBoolean($value, $defaultValue)
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $defaultValue
            : ((int) $value
                ? filter_var($value, FILTER_VALIDATE_INT)
                : false
            );

        return $value;
    }
}


if (! function_exists('toSafeArray')) {

    function toSafeArray($value)
    {
        if (isset($value)) {
            return is_array($value) ? $value : array($value);
        }
        return array();
    }
}

if (! function_exists('isValidStringOrArray')) {

    function isValidStringOrArray($value)
    {
        return (is_array($value) && count($value) > 0) || (is_string($value) && $value != '');
    }
}

if (! function_exists('parseDateTime')) {

    function parseDateTime($value, $format, $defaultValue = '', $defaultWillBeParsed = false)
    {
        return isset($value) ? Carbon::parse($value)->format($format) : ($defaultWillBeParsed ? Carbon::parse($defaultValue)->format($format) : $defaultValue);
    }
}

if (! function_exists('parseSqlDateTime')) {

    function parseSqlDateTime($value, $defaultValue = null)
    {
        return isset($value)
            ? Carbon::parse($value)->format('Y-m-d H:i:s')
            : ($defaultValue !== null
                ? Carbon::parse($defaultValue)->format('Y-m-d H:i:s')
                : null);
    }
}

if (! function_exists('cleanNonAlphaNumericChars')) {
    function cleanNonAlphaNumericChars($value, $withSpace = false)
    {
        if ($withSpace) {
            return preg_replace('/[^a-zA-Z0-9] /', '', $value);
        }
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }
}

if (! function_exists('genericGroupBy')) {
    function genericGroupBy($array, $key) {
        $return = array();
        
        foreach ($array as $val) {
            $return[$val->$key][] = $val; 
        //  $return[$val[$key]][] = $val; 
        }
        return $return;
    }
}

if (! function_exists('urlToDomain')) {
    function urlToDomain($url)
    {
        $input = trim($url, '/');
        if (! preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }
        $urlParts = parse_url($input);
        $domain = preg_replace('/^www\./', '', $urlParts['host']);

        return $domain;
    }
}

if (! function_exists('getDomain')) {
    function getDomain($url, $includeScheme = true)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $includeScheme ? $pieces['scheme'].'://'.$regs['domain'] : $regs['domain'];
        }
        return false;
    }
}

if (! function_exists('normalizeDomain')) {
    function normalizeDomain($url, $includeScheme = true)
    {
        // Parse the URL
        $pieces = parse_url($url);

        if (!isset($pieces['host'])) {
            // If 'host' is not set, maybe URL is missing scheme, try parsing manually
            $host = preg_replace('/\/.*$/', '', $pieces['path'] ?? '');
        } else {
            $host = $pieces['host'];
        }

        // Build domain with optional scheme
        $domain = $includeScheme && isset($pieces['scheme']) 
            ? $pieces['scheme'] . '://' . $host 
            : $host;

        // Remove trailing slash if exists
        $domain = rtrim($domain, '/');

        return $domain ?: false;
    }
}

if (! function_exists('removeNonAlphaNumeric')) {

    function removeNonAlphaNumeric($value)
    {
        $string = preg_replace('/[^a-zA-Z0-9\']/', '_', strtolower($value));
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', strtolower($string));

        return $string;
    }
}
