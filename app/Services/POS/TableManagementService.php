<?php

namespace App\Services\POS;

use App\Enums\POS\TableStatus;
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

    public function getTables($locationId = null, $withLocation = false, $swapId = false)
    {
        $query = $this->diningTableRepository;
        if ($withLocation) {
            $this->diningTableRepository->with('location');
        }

        if (!empty($locationId)) {
            $query = $query->findWhere(['location_id' => (int) $locationId]);
        } else {
            $query = $query->all();
        }

        /**
         * ID SWAPPING FOR STATION OTS INTEGRATION
         * 
         * When $swapId = true, we swap the ID references to match Station OTS expectations:
         * - Station OTS is the external Point of Sale (POS) system that sends table data
         * - It uses its own unique table IDs (pos_table_id) that need to be recognized by Station OTS
         * - Catapult maintains a separate local database with its own auto-incrementing IDs (id)
         * 
         * REQUIREMENTS FROM STATION OTS:
         * Station OTS requires that when it queries tables, the "id" field contains the POS database ID,
         * so it can reliably map responses back to its own records. Without this, Station OTS cannot
         * correlate Catapult responses with its internal table references.
         * 
         * RESPONSE STRUCTURE:
         * Default (swap_id=false): id = Catapult DB ID, pos_table_id = POS ID
         * Swapped (swap_id=true):  id = POS ID, catapult_table_id = Catapult DB ID
         * 
         * This allows Station OTS to:
         * 1. Receive responses with its native POS table IDs
         * 2. Maintain local state/properties based on POS IDs
         * 3. Update/reference tables using POS IDs as primary identifiers
         */
        if ($swapId) {
            $query = collect($query)->map(function ($table) {
                $catapultId = $table->id;
                $table->id = $table->pos_table_id;
                $table->catapult_table_id = $catapultId;
                return $table;
            });
        }

        return $query;
    }

    public function getLocations($withTables = false, $swapId = false)
    {
        if ($withTables) {
            $locations = $this->tableLocationRepository->with('tables')->all();
        } else {
            $locations = $this->tableLocationRepository->all();
        }

        /**
         * ID SWAPPING FOR STATION OTS INTEGRATION
         * 
         * When $swapId = true, we swap the ID references to match Station OTS expectations:
         * - Station OTS is the external Point of Sale (POS) system that sends location data
         * - It uses its own unique location IDs (pos_location_id) that need to be recognized by Station OTS
         * - Catapult maintains a separate local database with its own auto-incrementing IDs (id)
         * 
         * REQUIREMENTS FROM STATION OTS:
         * Station OTS requires that when it queries locations, the "id" field contains the POS database ID,
         * so it can reliably map responses back to its own location records. Without this, Station OTS cannot
         * maintain consistent location references and table-to-location relationships.
         * 
         * ENTITY/MODEL PROPERTIES RETENTION:
         * By performing ID swapping at the service layer, we preserve all other entity properties:
         * - location_name (location name from POS)
         * - no_of_tables (count of tables at location)
         * - no_of_seats (total seating capacity)
         * - status (location status: 1=active, 0=inactive)
         * - timestamps (created_at, updated_at)
         * - tables relationship (if $withTables=true)
         * 
         * RESPONSE STRUCTURE:
         * Default (swap_id=false): id = Catapult DB ID, pos_location_id = POS ID
         * Swapped (swap_id=true):  id = POS ID, catapult_location_id = Catapult DB ID
         * 
         * This allows Station OTS to:
         * 1. Receive responses with its native POS location IDs
         * 2. Maintain accurate location-to-table mappings using POS IDs
         * 3. Update location properties and track changes using POS IDs as primary identifiers
         * 4. Sync local state while preserving all Catapult-enriched data
         */
        if ($swapId) {
            $locations = collect($locations)->map(function ($location) {
                $catapultId = $location->id;
                $location->id = $location->pos_location_id;
                $location->catapult_location_id = $catapultId;
                return $location;
            });
        }

        return $locations;
    }

    public function upsertLocation($data = [])
    {
        if (is_array($data) && !isset($data['location_name']) && !isset($data['no_of_tables'])) {
            // Old format - single location
            return $this->tableLocationRepository->updateOrCreateById(
                isset($data['id']) ? (int) $data['id'] : null,
                [
                    'name' => $data['name'],
                    'status' => isset($data['status']) ? (int) $data['status'] : 1,
                ]
            );
        }

        // New format - single location with new fields
        return $this->tableLocationRepository->updateOrCreateById(
            null,
            [
                'pos_location_id' => isset($data['id']) ? (int) $data['id'] : null,
                'location_name' => $data['location_name'] ?? null,
                'name' => $data['location_name'] ?? null,
                'no_of_tables' => $data['no_of_tables'] ?? 0,
                'no_of_seats' => $data['no_of_seats'] ?? 0,
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
            ]
        );
    }

    public function upsertLocationBatch($dataArray = [])
    {
        $results = [];
        foreach ($dataArray as $data) {
            $data = (object) $data;
            $result = $this->tableLocationRepository->updateOrCreateByPosId(
                isset($data->id) ? (int) $data->id : null,
                [
                    'pos_location_id' => isset($data->id) ? (int) $data->id : null,
                    'location_name' => $data->location_name ?? null,
                    'name' => $data->location_name ?? null,
                    'no_of_tables' => $data->no_of_tables ?? 0,
                    'no_of_seats' => $data->no_of_seats ?? 0,
                    'status' => isset($data->status) ? (int) $data->status : 1,
                ]
            );
            $results[] = $result;
        }
        return $results;
    }

    public function upsertTable($data = [])
    {
        if (is_array($data) && !isset($data['table_ref']) && !isset($data['pos_table_id'])) {
            // Old format
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

        // New format with additional fields
        return $this->diningTableRepository->updateOrCreateById(
            null,
            [
                'pos_table_id' => isset($data['id']) ? (int) $data['id'] : null,
                'location_id' => isset($data['location_id']) ? (int) $data['location_id'] : null,
                'transaction_no' => $data['transaction_no'] ?? null,
                'table_ref' => $data['table_ref'] ?? null,
                'name' => $data['table_ref'] ?? null,
                'seat_number' => $data['seat_number'] ?? 0,
                'is_available' => $data['is_available'] ?? true,
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
                'availability' => $data['is_available'] ? 'available' : 'occupied',
                'date' => $data['date'] ?? null,
                'total' => $data['total'] ?? 0,
                'number_of_guest' => $data['number_of_guest'] ?? 0,
                'shape' => $data['shape'] ?? null,
                'positionX' => $data['position_x'] ?? 0,
                'position_y' => $data['position_y'] ?? 0,
                'height' => $data['height'] ?? 0,
                'width' => $data['width'] ?? 0,
                'angle' => $data['angle'] ?? 0,
                'is_placed' => $data['is_placed'] ?? false,
                'no_of_items' => $data['no_of_items'] ?? 0,
            ]
        );
    }

    public function upsertTableBatch($dataArray = [])
    {
        $results = [];
        foreach ($dataArray as $data) {
            $data = (object) $data;
            
            /**
             * FOREIGN KEY MAPPING: pos_location_id → table_location.id
             * 
             * Station OTS sends location_id as pos_location_id (POS system's own ID).
             * But the table.location_id foreign key references table_location.id (Catapult's ID).
             * 
             * Example:
             * - Request: location_id = 4 (Station OTS reference)
             * - Database: table_location record with pos_location_id=4 has id=3
             * - Solution: Look up id=3 and use it for foreign key constraint
             */
            $actualLocationId = null;
            if (isset($data->location_id)) {
                // The location_id in request is a pos_location_id from Station OTS
                // Find the actual table_location.id using pos_location_id
                $location = $this->tableLocationRepository->findWhere([
                    'pos_location_id' => (int) $data->location_id
                ])->first();
                
                if ($location) {
                    $actualLocationId = $location->id;
                }
            }
            
            $result = $this->diningTableRepository->updateOrCreateByPosId(
                isset($data->id) ? (int) $data->id : null,
                [
                    'pos_table_id' => isset($data->id) ? (int) $data->id : null,
                    'location_id' => $actualLocationId,
                    'transaction_no' => $data->transaction_no ?? null,
                    'table_ref' => $data->table_ref ?? null,
                    'name' => $data->table_ref ?? null,
                    'seat_number' => $data->seat_number ?? 0,
                    'is_available' => TableStatus::fromValue($data->is_available)->value == TableStatus::AVAILABLE,
                    'status' => isset($data->status) ? (int) $data->status : 1,
                    'availability' => strtolower(TableStatus::getDescription($data->is_available)),
                    'date' => $data->date ?? null,
                    'total' => $data->total ?? 0,
                    'number_of_guest' => $data->number_of_guest ?? 0,
                    'shape' => $data->shape ?? null,
                    'positionX' => $data->position_x ?? 0,
                    'position_y' => $data->position_y ?? 0,
                    'height' => $data->height ?? 0,
                    'width' => $data->width ?? 0,
                    'angle' => $data->angle ?? 0,
                    'is_placed' => $data->is_placed ?? false,
                    'no_of_items' => $data->no_of_items ?? 0,
                ]
            );
            $results[] = $result;
        }
        return $results;
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
        if ( $data['availability'] === 'available') {
            $table->is_available = true;
        } else {
            $table->is_available = false;
        }
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
