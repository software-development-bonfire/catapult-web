<?php

namespace App\Helpers;

use App\Traits\ConsoleCommandTrait;
use App\Traits\GenericHelper;
use Illuminate\Support\Facades\Log;

class CustomNetworkResolver
{
    use GenericHelper;
    use ConsoleCommandTrait;

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
        $output = [];

        $execString = 'netsh int ip reset';

        $this->printCommandEntry($execString);

        $output = $this->executeCommandBackground($execString);

        $output = $this->parseOutput($output);

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
        $output = [];

        $execString = 'ipconfig /flushdns';

        $output = $this->executeCommand($execString);

        if (! empty($output[1]) && ($output[1] === $this->expectedFlushOuputMessage)) {
            $flushed = true;
        }

        return $flushed;
    }

    /**
     * Get all connected network interface
     * 
     * @return array
     *   List of all connected network interface name
     */
    public function setDnsConnectedInterface($setDns = true)
    {
        $useProcessCommand = false;
        $connectedInterfaces = [];
        $output = [];

        // This command will show the list of network interface 
        $execString = 'netsh interface show interface';

        if ($useProcessCommand) {
            $output = $this->executeCommandProcess($execString);
        } else {
            $output = $this->executeCommand($execString);
        }

        // Parse response and get only connected network
        for ($index = 0; $index < count($output); $index++) {
            $line = $output[$index];
            // Filter output having line that contains defined patterns
            if (preg_match('/(Enabled|Disabled)\s+(Connected)\s+(Dedicated|Other)\s+(.*)/', $line, $match)) {
                array_shift($match);
                list($adminState, $state, $type, $name) =  $match;
                if (strtoupper($adminState) == $this->ENABLED &&  strtoupper($state) === $this->CONNECTED) {
                    $connectedInterfaces[] = $name;
                    if ($setDns) {
                        $this->setDns($name);
                    }
                }
            }
        }

        return $connectedInterfaces;
    }

    /**
     * We need to set local network DNS
     * using defined Google DNS
     */
    public function setDns($interfaceName, $process = true)
    {
        $result = [];
        // List of command to be executed in settings DNS of specified network
        // 1. This will set as STATIC source rather than DHCP, then clear existing address to avoid error
        // 2. This will set the Primary/Preferred DNS server of the network
        // 2. This will set the Alternate DNS server of the network
        $commands = [
            'netsh interface ip set dns name="'.$interfaceName.'" source=static addr=none',
            'netsh interface ip add dns name="'.$interfaceName.'" addr=8.8.8.8 index=1',
            'netsh interface ip add dns name="'.$interfaceName.'" addr=8.8.4.4 index=2'
        ];

        foreach ($commands as $command) {
            $output = [];

            if ($process) {
                $output = $this->executeCommandProcess($command);
            } else {
                $output = $this->executeCommand($command);
            }

            if (! empty($output[0])) {
                // Check if there is an output and ignored expected message after executing
                // first command in clearing existing configured addresses
                if ($output[0] !== $this->expectedSetClearDnsMessage) {
                    $result[] = "$command --> $output[0]";
                }
            }
        }
        return count($result) == 0 ? true : $result;
    }

    /**
     * Show configured DNS on specific interface name
     */
    public function showSetDns($interfaceName)
    {
        $output = [];

        // This commad will show the configured DNS servers of specified interface
        // both primary and alternate addresses
        $execString = 'netsh interface ipv4 show dnsservers "'.$interfaceName.'"';

        return $this->executeCommand($execString);
    }

    public function getDnsServers($interfaceName) {
        $output = $this->showSetDns($interfaceName);
        $index = 0;
        $dns = [];
        foreach ($output as $line) {
            if (preg_match('/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/', $line, $match)) {
                if ($index == 0) {
                    $dns['primary'] = $match[0];
                } else if ($index == 1) {
                    $dns['secondary'] = $match[0];
                }
                $index++;
            }
        }
        return count($dns) > 0 ? $dns : false;
    }
}
