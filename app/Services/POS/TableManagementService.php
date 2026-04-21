<?php

namespace App\Services\POS;

use App\Repositories\Contracts\POS\DiningTableRepository;
use App\Repositories\Contracts\POS\TableLocationRepository;
use App\Repositories\Contracts\POS\TerminalTransactionAvailabilityRepository;

class TableManagementService
{
    private const TABLE_AVAILABILITY = ['occupied', 'available', 'reserved'];
    private const TRANSACTION_AVAILABILITY = ['available', 'unavailable'];

    protected $tableLocationRepository;
    protected $diningTableRepository;
    protected $terminalTransactionAvailabilityRepository;

    public function __construct(
        TableLocationRepository $tableLocationRepository,
        DiningTableRepository $diningTableRepository,
        TerminalTransactionAvailabilityRepository $terminalTransactionAvailabilityRepository
    ) {
        $this->tableLocationRepository = $tableLocationRepository;
        $this->diningTableRepository = $diningTableRepository;
        $this->terminalTransactionAvailabilityRepository = $terminalTransactionAvailabilityRepository;
    }

    public function getTableAvailabilityValues()
    {
        return self::TABLE_AVAILABILITY;
    }

    public function getTransactionAvailabilityValues()
    {
        return self::TRANSACTION_AVAILABILITY;
    }

    public function getTables($locationId = null)
    {
        $query = $this->diningTableRepository->with('location');

        if (!empty($locationId)) {
            $query = $query->findWhere(['location_id' => (int) $locationId]);
        } else {
            $query = $query->all();
        }

        return $query;
    }

    public function getLocations()
    {
        return $this->tableLocationRepository->with('tables')->all();
    }

    public function upsertLocation($data = [])
    {
        return $this->tableLocationRepository->updateOrCreateById(
            isset($data['id']) ? (int) $data['id'] : null,
            [
                'name' => $data['name'],
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
            ]
        );
    }

    public function upsertTable($data = [])
    {
        return $this->diningTableRepository->updateOrCreateById(
            isset($data['id']) ? (int) $data['id'] : null,
            [
                'location_id' => isset($data['location_id']) ? (int) $data['location_id'] : null,
                'name' => $data['name'],
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
                'availability' => $data['availability'] ?? 'available',
            ]
        );
    }

    public function updateTableAvailability($data = [])
    {
        $table = $this->diningTableRepository->findByIdOrName(
            $data['id'] ?? null,
            $data['name'] ?? null,
            $data['location_id'] ?? null
        );

        if (!$table) {
            return null;
        }

        $table->availability = $data['availability'];
        $table->save();

        return $table->fresh();
    }

    public function checkTableAvailability($data = [])
    {
        $table = $this->diningTableRepository->findByIdOrName(
            $data['id'] ?? null,
            $data['name'] ?? null,
            $data['location_id'] ?? null
        );

        if (!$table) {
            return null;
        }

        return [
            'id' => $table->id,
            'name' => $table->name,
            'location_id' => $table->location_id,
            'availability' => $table->availability,
            'is_available' => $table->availability === 'available',
        ];
    }

    public function updateTransactionAvailability($data = [])
    {
        return $this->terminalTransactionAvailabilityRepository->updateAvailabilityByBidOrTransactionId(
            $data['bid'] ?? null,
            $data['transaction_id'] ?? null,
            $data['availability']
        );
    }

    public function checkTransactionAvailability($data = [])
    {
        $transaction = $this->terminalTransactionAvailabilityRepository->findByBidOrTransactionId(
            $data['bid'] ?? null,
            $data['transaction_id'] ?? null
        );

        if (!$transaction) {
            return null;
        }

        return [
            'bid' => $transaction->bid,
            'transaction_id' => $transaction->transaction_id,
            'availability' => $transaction->availability,
            'is_available' => $transaction->availability === 'available',
        ];
    }
}
