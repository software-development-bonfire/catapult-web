<?php

namespace App\Traits;

use App\Entities\CDISSync;
use App\Enums\DisplayState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait GenerateTrait
{
    use JobCancellationTrait;

    public function setGenerated($model, $bids, $isGenerated = DisplayState::YES)
    {
        $data = [
            'is_generated' => $isGenerated,
            'generated_at' => Carbon::now(),
        ];
        $model = $model->whereIn('bid', is_array($bids) ? $bids : array($bids));
        if ($model) {
            $model->update($data);
        }
    }

    public function setBranchGenerated()
    {
        Storage::disk('public')->put('CSV.info', 'This file means Create CSV for New Branch already executed.');
    }

    public function isBranchGenerated()
    {
        return (Storage::disk('public')->exists('CSV.info'));
    }

    /**
     * Build sync entry if entity data is not empty
     */
    private function buildEntitySyncEntry($entityData, $tableName, $branch, $cancellable = true, $specifyBranch = true)
    {
        $count = 0;
        foreach ($entityData as $entityDatum) {
            if ($cancellable) {
                $hasBeenCancelled = $this->hasBeenCancelledConversion();
                if ($hasBeenCancelled) {
                    break;
                }
            }

            $action = 'create';
            if ($this->modelHasColumn($entityDatum, $tableName, 'deleted_at')) {
                if ($entityDatum->deleted_at !== null) {
                    $action = 'delete';
                } else {
                    if (
                        $this->modelHasColumn($entityDatum, $tableName, 'created_at') &&
                        $this->modelHasColumn($entityDatum, $tableName, 'updated_at')
                    ) {
                        if ($entityDatum->created_at !== $entityDatum->updated_at) {
                            $action = 'update';
                        }
                    }
                }
            } else {
                if (
                    $this->modelHasColumn($entityDatum, $tableName, 'created_at') &&
                    $this->modelHasColumn($entityDatum, $tableName, 'updated_at')
                ) {
                    if ($entityDatum->created_at !== $entityDatum->updated_at) {
                        $action = 'update';
                    }
                }
            }
            $syncDetails = $entityDatum->syncDetails();

            if ($this->modelHasColumn($entityDatum, $tableName, 'branch_bid')) {
                if ($entityDatum->branch_bid !== $branch->bid) {
                    continue;
                }
            }

            CDISSync::create(
                array(
                    'branch_bid' => $branch->bid,
                    'table_bid' => $entityDatum->bid,
                    'table_name' => $tableName,
                    'reference_bid' => $syncDetails->reference_bid ?? null,
                    'reference_table' => $syncDetails->reference_table ?? null,
                    'level' => 1,
                    'group' => null,
                    'code' => $this->generateRandomKey(10, 1, ''),
                    'action' => $action,
                )
            );

            $count++;
        }
        return $count;
    }
}
