<?php

namespace App\Helpers;

use App\Traits\GenericHelper;
use Illuminate\Support\Facades\Log;

class CustomNetworkResolver
{
    use GenericHelper;

    private $lineSeparator = "......................................................................";
    private $expectedFlushOuputMessage = 'Successfully flushed the DNS Resolver Cache.';

    private $commandOutput;
    /**
     * Return the command output when method=exec.
     * @return string
     */
    public function getCommandOutput()
    {
        return $this->commandOutput;
    }

    /**
     * We need to reinitiate network protocol installed
     * the machine, send command to reset ip
     * 
     * @return boolean
     *   The result after resetting network proocol
     */
    public function resolve($background = true)
    {
        $resetted = false;

        $execString = 'netsh int ip reset';

        $this->printCommandEntry($execString);

        // Exec string for Windows-based systems.
        // Other OS is not yet supported
        if ($background && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B ".$execString, "r"));
        } else {
            exec($execString, $output, $return);
        }

        // Strip empty lines and reorder the indexes from 0 (to make results more
        // uniform across OS versions).
        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        $this->printOutput($output);
        // If the result line in the output is not empty, parse it.
        if (! empty($output[1])) {
            $resetted = true;
        }

        return $resetted;
    }

    /**
     * We need to flush dns to resolve some DNS caching
     * and to resolve some issues in website browsing experience
     * 
     * @return boolean
     *   The result after flushing DNS
     */
    public function flushDns()
    {
        $flushed = false;

        $execString = 'ipconfig /flushdns';

        $this->printCommandEntry($execString);

        // Exec string for Windows-based systems.
        // Other OS is not yet supported
        exec($execString, $output, $return);

        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        $this->printOutput($output);

        if (! empty($output[1]) && ($output[1] === $this->expectedFlushOuputMessage)) {
            $flushed = true;
        }

        return $flushed;
    }

    /**
     * Log command entry via default laravel logging system
     * It will helps to determine what commands being executed
     * and if in-case there are problems encountered, this may helps
     * for debugging purpose
     */
    private function printCommandEntry($commandString)
    {
        Log::info($this->lineSeparator);
        Log::info("Executing {$commandString}...");
    }

    /**
     * Print the command output to display as exact CLI output
     * when method=exec, this will work on Windows OS only
     */
    private function printOutput($output)
    {
        if (isset($output) && is_array($output)) {
            foreach ($output as $line) {
                Log::info($line);
            }
        }
    }
}
