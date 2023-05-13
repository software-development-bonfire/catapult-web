<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait ConsoleCommandTrait
 * @package App\Traits
 */
trait ConsoleCommandTrait
{
    public $commandOutput = '';

    public $lineSeparator = "......................................................................";
    public $expectedFlushOuputMessage = 'Successfully flushed the DNS Resolver Cache.';
    public $expectedSetClearDnsMessage =  'There are no Domain Name Servers (DNS) configured on this computer.';
    public $expectedDnsThroughDHCPMessage = 'DNS servers configured through DHCP';
    public $CONNECTED = "CONNECTED";
    public $ENABLED = "ENABLED";


    /**
     * Execute command by calling exec function for Windows-based System
     * Other OS, is not yet supported
     * 
     * @return array
     * Array of command result
     */
    public function executeCommand($execString, $laravelLog = true, $prefix = '')
    {
        $output = [];
        $this->printCommandEntry($execString, $laravelLog, $prefix);

        // Exec string for Windows-based systems.
        // Other OS is not yet supported
        exec($execString, $output, $return);

        $output = $this->parseOutput($output);

        $this->printOutput($output);

        return $output;
    }

    /**
     * Parse command output and print if set to true
     */
    public function parseOutput($output, $print = true)
    {
        // Strip empty lines and reorder the indexes from 0 (to make results more
        // uniform across OS versions).
        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        if ($print) {
            $this->printOutput($output);
        }

        return $output;
    }

    /**
     * Log command entry via default laravel logging system
     * bydefault, otherwise it log as a console log
     * It will helps to determine what commands being executed
     * and if in-case there are problems encountered, this may helps
     * for debugging purpose
     */
    private function printCommandEntry($commandString, $laravelLog = true, $prefix = '')
    {
        if ($laravelLog) {
            Log::info($this->lineSeparator);
            Log::info("Executing {$commandString}...");
        } else {
            $this->info("{$prefix} Executing <{$commandString}>...");
        }
    }

    /**
     * Print the command output to display as exact CLI output
     * when method=exec, this will work on Windows OS only
     */
    private function printOutput($output, $laravelLog = true)
    {
        if (isset($output) && is_array($output)) {
            foreach ($output as $line) {
                if ($laravelLog) {
                    Log::info($line);
                } else {
                    $this->info($line);
                }
            }
        }
    }

    private function computedLogLabel($maxLength, $label)
    {
        $spaces = ($maxLength - strlen($label)) / 2;
        return str_repeat(' ', ceil($spaces)).$label.str_repeat(' ', floor($spaces));
    }
}
