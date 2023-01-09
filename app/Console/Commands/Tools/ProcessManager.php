<?php

namespace App\Console\Commands\Tools;

use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:pm2 {name?} {--with=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and uninstall PM2';

    private $commandOutput = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $commandArgument = $this->argument('name');
        $with = $this->option('with');

        if (isset($commandArgument)) {
            if ($commandArgument === 'install') {
                // If <install> argument is entered, then execute the pre-defined
                // command collection to install PM2
                $this->executeCommand("npm install pm2 -g");
                $this->executeCommand("npm install pm2-windows-startup -g");
                $this->executeCommand("pm2 start ecosystem.config.js");
                $this->executeCommand("pm2 save");
                // If <with> option is present and equals to <startup>, means
                // installed processes must be run as services to automatically
                // runs during windows startup
                if ($with === 'startup') {
                    $this->executeCommand("pm2-startup install");
                    $this->executeCommand("pm2 save");
                }
            } else  if ($commandArgument === 'uninstall') {
                // If with option is present and equals to startup, means the configured 
                // auto-start must be disabled before installation of PM2. Sometimes the 
                // error will occur, it happens due to version support but just ignore it.
                if ($with === 'startup') {
                    $this->executeCommand("pm2 unstartup");
                    $this->executeCommand("pm2-startup uninstall");
                }
                // Kill running processes
                // Removed installed pm2
                // Forcely remove pm2 configuration
                $this->executeCommand("pm2 kill");
                $this->executeCommand("npm remove pm2 -g");
                $this->executeCommand("npm rm -rf ~/.pm2");
            }
        } else {
            // If no arguments are entered in the command, then execute optimize 
            // as the default command. To make sure optimization will take effect, 
            // we must stop the running PM2
            $this->executeCommand("pm2 stop all");
            $this->call("optimize");
            $this->executeCommand("pm2 restart all");
        }
    }

    /**
     * We need to reinitiate network protocol installed
     * the machine, send command to reset ip
     * 
     * @return boolean
     *   The result after resetting network proocol
     */
    public function executeCommand($execString)
    {
        $this->printCommandEntry($execString);

        // Exec string for Windows-based systems.
        // Other OS is not yet supported
        exec($execString, $output, $return);

        // Strip empty lines and reorder the indexes from 0 (to make results more
        // uniform across OS versions).
        $this->commandOutput = implode('', $output);
        $output = array_values(array_filter($output));

        $this->printOutput($output);
    }


    /**
     * Print command entry to display comand in CLI
     * It will helps to determine what commands being executed
     * and if in-case there are problems encountered, this may helps
     * for debugging purpose
     */
    private function printCommandEntry($commandString)
    {
        $this->info("[POS] Executing <{$commandString}>...");
    }

    /**
     * Print the command output to display as exact CLI output
     * when method=exec, this will work on Windows OS only
     */
    private function printOutput($output)
    {
        if (isset($output) && is_array($output)) {
            foreach ($output as $line) {
                $this->info($line);
            }
        }
    }
}
