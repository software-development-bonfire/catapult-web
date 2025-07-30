<?php

namespace App\Console\Commands\EcomToPOS\Events;

use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;

class EcomPing extends Command
{
    use GenericHelper, PusherTrait;

    protected $signature = 'ecom:event-ping 
                            {--interval=5 : Delay between pings in seconds}
                            {--limit= : Optional max number of pings (leave blank for unlimited)}';

    protected $description = 'Continuously sends ping events to the Pusher channel until stopped or limit reached.';

    public function handle()
    {
        $pusherDomain = 'ws-eu.pusher.com';
        $interval = (int) $this->option('interval');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $pingCount = 0;
        $success = 0;
        $loss = 0;

        $this->info("Starting Ecom Ping every {$interval}s" . ($limit ? " (limit: {$limit} pings)" : " (unlimited)"));

        while (is_null($limit) || $pingCount < $limit) {
            if (
                $this->hasInternetConnection() &&
                $this->hasInternetConnection($pusherDomain)
            ) {
                try {
                    $this->ping($pingCount);
                    $success++;
                } catch (\Exception $e) {
                    $this->createLog("❌ Ping failed: ". $e->getMessage(), 'error', true, ['PING ERROR']);
                }
            } else {
                $this->createLog("⚠️ No internet. Retrying after {$interval}s...", 'warn', true, ['CONNECTION ERROR']);
                $loss++;
            }

            $pingCount++;
            sleep($interval);
        }

        $this->info("🛑 Ecom Ping ended. Total pings sent: {$pingCount}, Success: {$success}, Loss: {$loss}");
    }

    protected function ping($pingCount)
    {
        $clientId = config('configuration.client_id');
        $branchCode = config('configuration.branch_code');
        $channel = 'ecommerce-online-branch-' . $clientId;

        $this->initializePusher();
        $this->pusher->trigger($channel, 'ping', $branchCode);
        
        $this->createLog("✅ Ping #".($pingCount + 1)." sent to branch: {$branchCode}.", "info", true, [$channel]);
    }
}