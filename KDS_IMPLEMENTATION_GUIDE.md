# Kitchen Display System (KDS) Implementation Guide

## Overview
This document describes the fully functional KDS (Kitchen Display System) implementation in Catapult POS. The system manages order flow from the POS terminal to the kitchen display devices, with support for station-to-station item movement.

## System Architecture

### Data Flow

```
POS Terminal (TerminalTransactionController)
    ↓
TerminalTransactionService (Data Construction)
    ↓
KitchenDisplayTrait (Database Storage)
    ↓
Broadcast Events (MyPrivateEvent, KDSTransactionEvent)
    ↓
Kitchen Display Devices (WebSocket clients)
```

## Components

### 1. **TerminalTransactionController** (`app/Http/Controllers/POS/v1/TerminalTransactionController.php`)
- **Purpose**: Receives transaction data from POS terminals
- **Key Methods**:
  - `store()`: Processes new transactions and initiates KDS broadcasts
  - Validates kitchen printer and sticker printer requirements
  - Groups products by device_uid for KDS broadcasting
  - Groups items by order_type_id for releasing station display

### 2. **TerminalTransactionService** (`app/Services/POS/TerminalTransactionService.php`)
- **Purpose**: Constructs complete transaction data structure
- **Key Features**:
  - Creates CDISTerminalTransaction records
  - Builds kitchen display details via KitchenDisplayTrait
  - Processes products and addons
  - Returns structured data for broadcasting

### 3. **KitchenDisplayTrait** (`app/Traits/KitchenDisplayTrait.php`)
- **Purpose**: Handles KDS database operations
- **Key Methods**:
  - `validateKitchenDisplay()`: Creates initial KDS records
  - `buildKitchenDisplay()`: Builds KitchenDisplay and KitchenDisplayDetail records
  - Supports up to 4 kitchen stations per product

### 4. **KitchenDisplayService** (`app/Services/KitchenDisplayService.php`)
- **Purpose**: Manages KDS operations after initial order creation
- **Key Methods**:
  - `moveMenu()`: Moves item to next station
  - `moveRowItem()`: Moves specific quantity to adjacent stations
  - `doneMenu()`: Marks item as complete
  - `doneOrder()`: Marks entire order as complete
  - `releaseOrder()`: Releases order to customer
  - `releaseRowItem()`: Releases specific item

### 5. **KitchenDisplayController** (`app/Http/Controllers/KDS/v1/KitchenDisplayController.php`)
- **Purpose**: Handles KDS API endpoints
- **Endpoints**:
  - `GET /api/kds/v1/order/menu/list` - Fetch menu list
  - `POST /api/kds/v1/order/move` - Move order between stations
  - `POST /api/kds/v1/order/menu/move-station` - Move menu item
  - `DELETE /api/kds/v1/order/remove` - Remove order
  - `DELETE /api/kds/v1/menu/remove` - Remove menu item

## Database Models

### KitchenDisplay (`app/Entities/KitchenDisplay.php`)
- Represents a transaction's kitchen display head record
- **Fields**:
  - `bid`: Unique identifier
  - `transaction_detail_bid`: Links to transaction detail
  - `completed_at`: Timestamp when order completed

### KitchenDisplayDetail (`app/Entities/KitchenDisplayDetail.php`)
- Represents individual items in kitchen display
- **Fields**:
  - `head_bid`: Links to KitchenDisplay
  - `transaction_id`: Transaction identifier
  - `transaction_product_bid`: Product identifier
  - `product_uom_packaging_bid`: Product UOM packaging
  - `kitchen_station_bid`: Current station
  - `kitchen_station_index`: Station number (1-4)
  - `remaining_quantity`: Quantity at this station
  - `status`: MenuStatus (ON_PROCESS, RELEASING, DONE)
  - `order_type_id`: Order type identifier
  - `order_type_name`: Order type name
  - `special_request`: Customer special requests
  - `is_addon`: Whether item is addon
  - `addons`: Addon details
  - `terminal_number`: Source terminal

## Broadcasting System

### Event Types

#### 1. **MyPrivateEvent** (`app/Events/MyPrivateEvent.php`)
- **Purpose**: Device-specific KDS order updates
- **Channel**: `private-kds-device-{device_uid}`
- **Use Case**: Sends items to specific kitchen display devices
- **Data**:
  - `device`: Device UID
  - `transaction`: Transaction details
  - `items`: Items for this device
  - `releasing`: Release info

#### 2. **KDSTransactionEvent** (`app/Events/KDSTransactionEvent.php`)
- **Purpose**: Order type specific releases
- **Channel**: `kds-channel`
- **Use Case**: Broadcasts to releasing/POS stations
- **Data**:
  - `device`: Order type ID
  - `transaction`: Transaction details
  - `items`: Items for this order type
  - `releasing`: Order type name
  - `type`: Event type (add, update, done)

#### 3. **KDSDoneEvent** (`app/Events/KDS/KDSDoneEvent.php`)
- **Purpose**: Notifies all KDS when order is complet
- **Channel**: `kds-channel`
- **Event Name**: `kds-done-event`

#### 4. **KDSReleaseEvent** (`app/Events/KDS/KDSReleaseEvent.php`)
- **Purpose**: Notifies KDS when order is ready for release
- **Channel**: `kds-channel`
- **Event Name**: `kds-release-event`

### Broadcasting Configuration (`routes/channels.php`)
```php
Broadcast::channel('kds-device-{device}', function ($user, $device) {
    return ['device' => $device];
});
```

## Station Movement Flow

### Moving Item to Next Station

1. **Request**: KDS sends `POST /api/kds/v1/order/menu/move-station`
   ```json
   {
     "item": {
       "transaction_id": "123",
       "product_uom_packaging_bid": "456",
       "terminal_number": "1",
       "kitchen_station_index": 1
     },
     "next": true,
     "quantity": 2
   }
   ```

2. **Processing** (KitchenDisplayService.moveRowItem):
   - Find current station item
   - Calculate next station index
   - If next station exists:
     - Add quantity to next station
     - Update next station record
     - Broadcast to next station's KDS device
   - If no next station:
     - Move to releasing station (index 0)
     - Broadcast release event

3. **Database Updates**:
   - Reduce current station quantity
   - Increase next station quantity
   - Update status if transitioning

4. **Broadcasting**:
   - Broadcast to next station's device via MyPrivateEvent
   - Broadcast release update via KDSTransactionEvent

## Troubleshooting

### Items not appearing on KDS
1. Check `device_uid` is properly set in kitchen setup
2. Verify device is connected via WebSocket
3. Check broadcast logs for errors
4. Ensure product has kitchen item setup configured (`Synced successfully from CDIS`)

### Station movement not working
1. Verify next station exists in database
2. Check quantity is not exceeding available
3. Review `kitchen_station_index` values
4. Check MenuStatus enum values match database

### Broadcasting failures
1. Verify Laravel broadcasting is configured
2. Check Pusher connection
3. Review channel authorization routes
4. Check WebSocket server logs

## Related Files

- [TerminalTransactionController](app/Http/Controllers/POS/v1/TerminalTransactionController.php)
- [KitchenDisplayService](app/Services/KitchenDisplayService.php)
- [KitchenDisplayTrait](app/Traits/KitchenDisplayTrait.php)
- [MyPrivateEvent](app/Events/MyPrivateEvent.php)
- [KDSTransactionEvent](app/Events/KDSTransactionEvent.php)
- [KitchenDisplay Entity](app/Entities/KitchenDisplay.php)
- [KitchenDisplayDetail Entity](app/Entities/KitchenDisplayDetail.php)

