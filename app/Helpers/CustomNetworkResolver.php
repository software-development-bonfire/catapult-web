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
     */
    public function resolve()
    {
        $resetted = false;

        $exec_string = 'netsh int ip reset';

        $this->printCommandEntry($exec_string);

        exec($exec_string, $output, $return);

        // Strip empty lines and reorder the indexes from 0 (to make results more
        // uniform across OS versions).
        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        $this->printOutput($output);
        // If the result line in the output is not empty, parse it.
        if (!empty($output[1])) {
            $resetted = true;
        }

        return $resetted;
    }

    /**
     * We need to flush dns to resolve some DNS caching
     */
    public function flushDns()
    {
        $resetted = false;

        $exec_string = 'ipconfig /flushdns';

        $this->printCommandEntry($exec_string);

        exec($exec_string, $output, $return);

        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        $this->printOutput($output);

        if (! empty($output[1])) {
            if ($output[1] === $this->expectedFlushOuputMessage) {
                $resetted = true;
            }
        }

        return $resetted;
    }

    private function printCommandEntry($commandString)
    {
        Log::info($this->lineSeparator);
        Log::info("Executing {$commandString}...");
    }

    private function printOutput($output)
    {
        if (isset($output) && is_array($output)) {
            foreach ($output as $line) {
                Log::info($line);
            }
        }
    }
}
