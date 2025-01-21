<?php

namespace App\Jobs\KDS;

use App\Traits\KitchenPrinterTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PrintToKitchenPrinter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use KitchenPrinterTrait;

    public $printer;
    public $items;
    public $transaction;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($printer, $items, $transaction)
    {
        $this->printer = $printer;
        $this->items = $items;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->printKitchen($this->printer, $this->items, $this->transaction);

        return;
    }
}
