<?php

namespace App\Console\Commands\Tools;

use App\Traits\ConsoleCommandTrait;
use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessManager extends Command
{
    use ConsoleCommandTrait;
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

    private $laravelLog = false;
    private $logPrefix = '[POS]';
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
                $this->executeCommand("npm install pm2 -g", $this->laravelLog, $this->logPrefix);
                $this->executeCommand("npm install pm2-windows-startup -g", $this->laravelLog, $this->logPrefix);
                $this->executeCommand("pm2 start ecosystem.config.js", $this->laravelLog, $this->logPrefix);
                $this->executeCommand("pm2 save", $this->laravelLog, $this->logPrefix);
                // If <with> option is present and equals to <startup>, means
                // installed processes must be run as services to automatically
                // runs during windows startup
                if ($with === 'startup') {
                    $this->executeCommand("pm2-startup install", $this->laravelLog, $this->logPrefix);
                    $this->executeCommand("pm2 save", $this->laravelLog, $this->logPrefix);
                }
            } else  if ($commandArgument === 'uninstall') {
                // If with option is present and equals to startup, means the configured 
                // auto-start must be disabled before installation of PM2. Sometimes the 
                // error will occur, it happens due to version support but just ignore it.
                if ($with === 'startup') {
                    $this->executeCommand("pm2-startup uninstall", $this->laravelLog, $this->logPrefix);
                }
                // Kill running processes
                // Removed installed pm2
                // Forcely remove pm2 configuration
                $this->executeCommand("pm2 kill", $this->laravelLog, $this->logPrefix);
                $this->executeCommand("npm remove pm2 -g", $this->laravelLog, $this->logPrefix);
                $this->executeCommand("npm rm -rf ~/.pm2", $this->laravelLog, $this->logPrefix);
            }
        } else {
            // If no arguments are entered in the command, then execute optimize 
            // as the default command. To make sure optimization will take effect, 
            // we must stop the running PM2
            $this->executeCommand("pm2 stop all", $this->laravelLog, $this->logPrefix);
            $this->call("optimize");
            $this->executeCommand("pm2 restart all", $this->laravelLog, $this->logPrefix);
        }
    }
}
