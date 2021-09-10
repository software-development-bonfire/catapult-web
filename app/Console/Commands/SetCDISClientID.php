<?php

namespace App\Console\Commands;

use App\Enums\CDISResponseCode;
use App\Traits\CatapultConfiguration;
use Illuminate\Console\Command;

class SetCDISClientID extends Command
{
    use CatapultConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:client_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the CDIS Client ID';

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
        $productKey = $this->ask('Enter Client ID');

        $attribute = 'client_id';

        if ($this->isAttributeExists($attribute)) {
            if ($this->confirm('Do you want to override current Client ID?')) {
                $this->updateAttribute([
                    'attribute' => $attribute,
                    'value' => $productKey
                ]);

                $this->info('Client ID successfully set!');
            }
        } else {
            $this->storeAttribute([
                'attribute' => $attribute,
                'value' => $productKey
            ]);

            $this->info('Client ID successfully set!');
        }
    }
}
