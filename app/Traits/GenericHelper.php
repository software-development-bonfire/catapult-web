<?php

namespace App\Traits;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DB;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait GenericHelper
{
    public function eliminateDuplicates($data, $keys, $duplicateLabel = '')
    {
        foreach ($keys as $key) {
            ${Str::camel($key)} = '';
        }

        foreach ($data as $datum) {
            foreach ($keys as $key) {
                $datum->$key = ${Str::camel($key)} === $datum->$key ? $duplicateLabel : ${Str::camel($key)} = $datum->$key;
            }
        }

        return $data;
    }

    public function getFileExtension($filename)
    {
        $explodedName = explode('.', $filename);
        return count($explodedName) > 1 ? end($explodedName) : '';
    }

    /**
     * Get minimum untaken number or value of the specific table column
     *
     * @param string    $table
     * @param string    $column
     * @param int|null  $min
     * @param int|null  $max
     * @param array     $conditions
     * @param bool      $withLeadingZeros
     * @param bool      $withTrashed
     * @return mixed
     */
    public function getMinUntakenValue(
        $table,
        $column = 'id',
        $min = null,
        $max = null,
        $conditions = array(),
        $withLeadingZeros = false,
        $withTrashed = false
    ) {
        DB::statement(DB::raw("SET @row := 0"));

        $model = DB::table($table.' AS tablename');

        if ($withLeadingZeros) {
            $lpadLength = ! is_null($max) && is_numeric($max) ? strlen($max) : 18;

            $model = $model->select([
                DB::raw('COALESCE(LPAD(MIN(N.numbers), '.$lpadLength.', \'0\'), \'\') AS `min`')
            ]);
        } else {
            $model = $model->select([
                DB::raw('COALESCE(MIN(N.numbers), \'\') AS `min`')
            ]);
        }

        $model = $model
            ->rightJoin(DB::raw(
                '(SELECT 
                        @row:=@row + 1 AS `numbers`
                    FROM
                        (SELECT 0
                            UNION ALL SELECT 1
                            UNION ALL SELECT 2
                            UNION ALL SELECT 3
                            UNION ALL SELECT 4
                            UNION ALL SELECT 5
                            UNION ALL SELECT 6
                            UNION ALL SELECT 7
                            UNION ALL SELECT 8
                            UNION ALL SELECT 9) A,
                        (SELECT 0
                            UNION ALL SELECT 1
                            UNION ALL SELECT 2
                            UNION ALL SELECT 3
                            UNION ALL SELECT 4
                            UNION ALL SELECT 5
                            UNION ALL SELECT 6
                            UNION ALL SELECT 7
                            UNION ALL SELECT 8
                            UNION ALL SELECT 9) B,
                        (SELECT 0
                            UNION ALL SELECT 1
                            UNION ALL SELECT 2
                            UNION ALL SELECT 3
                            UNION ALL SELECT 4
                            UNION ALL SELECT 5
                            UNION ALL SELECT 6
                            UNION ALL SELECT 7
                            UNION ALL SELECT 8
                            UNION ALL SELECT 9) C,
                        (SELECT 0
                            UNION ALL SELECT 1
                            UNION ALL SELECT 2
                            UNION ALL SELECT 3
                            UNION ALL SELECT 4
                            UNION ALL SELECT 5
                            UNION ALL SELECT 6
                            UNION ALL SELECT 7
                            UNION ALL SELECT 8
                            UNION ALL SELECT 9) D,
                        (SELECT @row:=0) AS E
                    '. (! is_null($max) ? 'LIMIT '. $max : '') .') AS N ON tablename.`'.$column.'` = N.`numbers`'
            ), function($query) {
                $query->select(DB::raw('@row:=@row + 1 AS `numbers`'));
            });

        if (! is_null($min)) {
            $model = $model->where('N.numbers', '>=', $min);
        }

        $model = $model->whereNotIn('N.numbers', function($query) use($table, $column, $conditions, $withTrashed) {
            $query->select($column)->from($table);

            if (count($conditions) > 0) {
                foreach ($conditions as $condition) {
                    $condition = stringToJson($condition);
                    $query->where($condition->column, $condition->operator, $condition->value);
                }
            }

            if (! $withTrashed) {
                $query->whereNull('deleted_at');
            }
        });

        return $model->pluck('min')[0];
    }

    /**
     * Get minimum untaken number or value of the specific table column between min and max
     *
     * @param string    $table
     * @param string    $column
     * @param int|null  $min
     * @param int|null  $max
     * @return mixed
     */
    public function getMinUntakenValueWithRange(
        $table,
        $column = 'id',
        $min = null,
        $max = null
    ) {
        DB::statement(DB::raw("SET @min_untaken_value = 0"));
        DB::statement(DB::raw("SET @min := ".$min));
        DB::statement(DB::raw("SET @max := ".$max));

        DB::statement(DB::raw(
            'SELECT
                COALESCE(MIN(T1.`'.$column.'`), @min) + 1
                INTO @min_untaken_value
            FROM
                `'.$table.'` AS T1
                LEFT JOIN `'.$table.'` AS T2 ON T2.`'.$column.'` = T1.`'.$column.'` + 1 AND T2.`deleted_at` IS NULL
            WHERE
                T1.`deleted_at` IS NULL
                AND T2.`'.$column.'` IS NULL
                AND T1.`'.$column.'` >= @min
                AND T1.`'.$column.'` <= @max
                AND LENGTH(T1.`'.$column.'`) = '.strlen($min).'
                AND T1.`'.$column.'` REGEXP \'[0-9]\'
            LIMIT 1'));

        $minUntakenValue = DB::selectOne(
            "SELECT IF(@min_untaken_value > 0, @min_untaken_value, @min) as min_untaken_value")
            ->min_untaken_value;

        return $minUntakenValue;
    }

    public function dayCount($day, $month, $year) {
        $count = 0;
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $date = new \Datetime($year.'-'.$month.'-01');
        $day = date('l', strtotime($year.'-'.$month.'-'.$day));
    
        for ($i = 0; $i < $days; $i++) {
            if($date->format('l') == $day) {
                 $count++;
            }

            $date->modify('+1 day');
        }

        return $count;
    }

    /**
     * Get missing dates from a date range array
     *
     * @param array $range sorted array values containing range
     * @return array $missingDates missing dates from array range
     */
    public function getMissingDates(array $range)
    {
        $missingDates = array();

        $start = date_create(reset($range));
        $end = date_create(end($range));

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $day) {
            $formatted = $day->format('Y-m-d');

            if(! in_array($formatted, $range)) $missingDates[] = $formatted;
        }

        return $missingDates;
    }

    /**
     * Create/construct formatted log message.
     *
     * @param string  $message
     * @param string  $logType
     * @param boolean  $hasDate
     * @param array  $prefixTags
     * @param array  $suffixTags
     * @param boolean  $console
     *
     * @return string  $constructedMessage
     */
    public function createLog($message = '', $logType = 'info', $hasDate = true, $prefixTags = [], $suffixTags = [], $console = true)
    {
        $date = $hasDate ? '['.Carbon::now()->format('Y-m-d H:i:s').']' : '';

        $prefixTagLabel = '';
        if (is_array($prefixTags) && count($prefixTags) > 0) {
            foreach ($prefixTags as $prefixTag) {
                $prefixTagLabel .= '['.$prefixTag.']';
            }
        }

        $suffixTagLabel = '';
        if (is_array($suffixTags) && count($suffixTags) > 0) {
            foreach ($suffixTags as $suffixTag) {
                $suffixTagLabel .= '('.$suffixTag.')';
            }
        }

        $constructedMessage = $date.$prefixTagLabel.' '.$message.' '.$suffixTagLabel;

        if ($console) {
            $this->{$logType}($constructedMessage);
        }

        return $constructedMessage;
    }

    /**
     * Generate log label base from give length.
     *
     * @param int  $maxLength
     * @param string  $logLabel
     *
     * @return string  $logLabel
     */
    public function createLogLabel($maxLength, $logLabel)
    {
        $spaces = ($maxLength - strlen($logLabel)) / 2;
        return str_repeat(' ', ceil($spaces)).$logLabel.str_repeat(' ', floor($spaces));
    }

    /**
     * Cache set of value.
     *
     * @param string  $key
     * @param string  $value
     * @param int  $ttl
     *
     * @return string  $constructedMessage
     */
    public function cacheSetOfValue($key, $value, $ttl = 60)
    {
        $savedValue = Cache::get($key) ?? [];

        $savedValue = (is_array($savedValue) && count($savedValue) > 0)
            ? $savedValue
            : [];

        if (! in_array($value, $savedValue)) {
            $savedValue[] = $value;
            Cache::put($key, $savedValue, $ttl);
        }
    }

    public function cdisAndCatapultSyncChannel($branchCode, $includeBranchCode = true)
    {
        $clientId = config('configuration.client_id');
        $cdisUrl = config()->get('app.cdis_url');
        $host = str_replace(':', '_', parse_url($cdisUrl, PHP_URL_HOST));
        $port = parse_url($cdisUrl, PHP_URL_PORT);

        if ($includeBranchCode) {
            return $host.(! is_null($port) ? '_'.$port : '').'_catapult_sync.'.$clientId.'_'.$branchCode;
        }
        return  $host.(! is_null($port) ? '_'.$port : '').'_catapult_sync.'.$clientId;
    }

    public function hasInternetConnection($target = "8.8.8.8", $port = 53, $timeout = 3): bool
    {
        // 1. If input is a full URL (starts with http/https)
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            $context = stream_context_create([
                'http' => ['method' => 'HEAD', 'timeout' => $timeout],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $stream = @fopen($target, 'r', false, $context);
            if ($stream) {
                fclose($stream);
                return true;
            }
            return false;
        }

        // 2. If it's a domain or IP, check multiple fallback ports
        $portsToTry = [$port, 80, 443, 53];
        foreach ($portsToTry as $p) {
            $connected = @fsockopen($target, $p, $errno, $errstr, $timeout);
            if ($connected) {
                fclose($connected);
                return true;
            }
        }

        return false;
    }

    public function hasInternetViaMultipleChecks(): bool
    {
        $hosts = [
            ["ip" => "1.1.1.1", "port" => 53],     // Cloudflare DNS
            ["ip" => "8.8.8.8", "port" => 53],     // Google DNS
            ["url" => "https://www.google.com"],   // Fallback HTTP
        ];

        foreach ($hosts as $target) {
            if (isset($target['ip'])) {
                if (@fsockopen($target['ip'], $target['port'], $errno, $errstr, 2)) {
                    return true;
                }
            } else if (isset($target['url'])) {
                $stream = @fopen($target['url'], "r");
                if ($stream) {
                    fclose($stream);
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Generate code for sync table entries
     *
     * @param integer $length
     * @param integer $partition
     * @param string $delimiter
     * @return string $key
     */
    public function generateRandomKey($length = 25, $partition = 5, $delimiter = "-")
    {
        $characters = "X9";
        $characterLength = strlen($characters);
        $randomString = "";

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $characterLength - 1)];
        }

        $format = rtrim(chunk_split($randomString, $partition, $delimiter), $delimiter);
        $formatLength = strlen($format);

        $key = "";
        for ($i = 0; $i < $formatLength; $i++) {
            switch ($format[$i]) {
                case "X": $key .= chr(rand(65, 90)); break;
                case "9": $key .= rand(0, 9); break;
                case $delimiter: $key .= $delimiter;  break;
            }
        }

        return $key;
    }

    public function secondsToHumanReadableTime($milliSeconds) 
    {
        $hours = floor($milliSeconds / 3600);
        $minutes = floor(($milliSeconds / 60) % 60);
        $seconds = $milliSeconds % 60;

        return ($hours > 0 ? "$hours hrs, $minutes mins" : 
               ($minutes > 0 ? "$minutes mins, $seconds secs" : 
               ($seconds > 0 ? "$seconds seconds" : "$milliSeconds ms")));
    }

    protected function modelHasColumn($model, $tableName = '', $columnName = '') 
    {
        if ($model === null) {
            return false;
        }
    
        if (empty($tableName)) {
            $tableName = $this->getTableName($model);
            if (empty($tableName)) {
                return false;
            }
        }
        
        return $model->getConnection()
        ->getSchemaBuilder()
        ->hasColumn($tableName , $columnName);
    }

    public function getEntityInformation($entity)
    {
        $entityName = str_replace('App\\Entities\\CDIS', '', $entity);
        return (object) [
            'has_soft_deleting' => in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity)),
            'entity_name' => $entityName,
            'table_name' => Str::snake($entityName)
        ];
    }
    
    public function getTableName($model) {
        $tableName = "";
        try {
            $tableName = $model->getTable();
        } catch (Exception $e) {
            try {
                $tableName = $model->tableName();
            } catch (Exception $ex) {

            }
        }
        return $tableName;
    }

    public  function getSqlWithBindings($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return preg_replace_callback('/\?/', function ($match) use ($sql, &$bindings) {
            return json_encode(array_shift($bindings));
        }, $sql);
    }
}
