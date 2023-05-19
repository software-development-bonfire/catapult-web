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
     * Execute command in background for Windows-based System
     * then if use default exec function to execute the command
     * if not supported
     * 
     * @return array
     * Array of command result
     */
    public function executeCommandBackground($execString, $laravelLog = true, $prefix = '')
    {
        $output = [];
        $this->printCommandEntry($execString, $laravelLog, $prefix);
        // Exec string for Windows-based systems.
        // Other OS is not yet supported
        if ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')) {
            pclose(popen("start /B " . $execString, "r"));
        } else {
            exec($execString, $output, $return);
        }
        $output = $this->parseOutput($output);

        $this->printOutput($output);

        return $output;
    }

    /**
     * Execute a command and open file pointers for input/output
     * 
     * @return array
     * Array of command result
     */
    function executeCommandProcess($cmd)
    {
        $in  = 4096;
        $out = '';
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        $options = null; // array('bypass_shell' => true);

        $process = proc_open($cmd, $descriptorspec, $pipes, null, null, $options);
        if (is_resource($process)) {
            fwrite($pipes[0], $in);
            /* fwrite writes to stdin, 'cat' will immediately write the data from stdin
			* to stdout and blocks, when the stdout buffer is full. Then it will not
			* continue reading from stdin and php will block here.
			*/
            fclose($pipes[0]);

            while (! feof($pipes[1])) {
                $out .= fgets($pipes[1], $in);
            }
            fclose($pipes[1]);
        }
        $returnCode = proc_close($process);
        return explode(PHP_EOL, $out);;
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
        if (isset($output) && ! is_array($output) && is_string($output)) {
            $output = array($output);
        }
        foreach ($output as $line) {
            if ($laravelLog) {
                Log::info($line);
            } else {
                $this->info($line);
            }
        }
    }

    private function computedLogLabel($maxLength, $label)
    {
        $spaces = ($maxLength - strlen($label)) / 2;
        return str_repeat(' ', ceil($spaces)) . $label . str_repeat(' ', floor($spaces));
    }
}
