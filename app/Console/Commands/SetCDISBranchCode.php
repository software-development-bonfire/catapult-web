<?php

namespace App\Console\Commands;

use App\Traits\CatapultConfiguration;
use Illuminate\Console\Command;

class SetCDISBranchCode extends Command
{
    use CatapultConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:branch_code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the CDIS Branch Code';

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
        $productKey = $this->ask('Enter Branch Code');

        $attribute = 'branch_code';

        if ($this->isAttributeExists($attribute)) {
            if ($this->confirm('Do you want to override current Branch Code?')) {
                $this->updateAttribute([
                    'attribute' => $attribute,
                    'value' => $productKey
                ]);

                $this->info('Branch Code successfully set!');
            }
        } else {
            $this->storeAttribute([
                'attribute' => $attribute,
                'value' => $productKey
            ]);

            $this->info('Branch Code successfully set!');
        }
    }
}
