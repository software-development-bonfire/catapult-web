<?php

namespace App\Console\Commands;

use App\Helpers\CustomNetworkResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NetworkTroubleshooter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:resolve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute defined network command, to resolve issue on network protocol and DNS caching';

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
        $cdisUrl = urlToDomain(config()->get('app.cdis_url'));
        $pusherDomain = 'ws-eu.pusher.com';

        $networkResolver = new CustomNetworkResolver();

        $this->info(__('info.resetting_network'));
        $networkResolver->resolve();

        $this->info(__('info.flushing_network'));
        $networkResolver->flushDns();

        $this->info(__('info.resolving_dns', ['domain' => "{$cdisUrl} and {$pusherDomain}"]));
        if (
            $this->resolveDns($cdisUrl) ||
            $this->resolveDns($pusherDomain)
        ) {
            $this->info('Success!');
        }
    }

    public function resolveDns($domain)
    {
        $resolved = true;
        try {
            $config = \React\Dns\Config\Config::loadSystemConfigBlocking();
            if (!$config->nameservers) {
                $config->nameservers[] = '8.8.8.8';
            }

            $factory = new \React\Dns\Resolver\Factory();
            $dns = $factory->create($config);

            $dns->resolve($domain)->then(function ($ip) {
                $this->info(__('info.domain_ip').': '.$ip);
            });
        } catch (\Exception $e) {
            $this->error('Resolving error: '.$e->getMessage());
            $resolved = false;
        }
        return $resolved;
    }
}
