<?php

namespace App\Traits;

use App\Enums\KDS\OrderType;
use App\Helpers\IP;
use App\Helpers\StickerLabel\StickerPrinter;
use Illuminate\Support\Carbon;

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Trait StickerPrinterTrait
 * @package App\Traits
 */
trait StickerPrinterTrait
{
    function printSticker($printerName, $data, $transaction, $cut = true, $openCashdrawer = true)
    {
        /* Start the printer */
        $validIPs = IP::extract($printerName);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector('XP-420B');
        }

        /* Information for the receipt */
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);
        $orderType = strtoupper(OrderType::getDescription($transaction['type']));
        $orderNumber = "Order #: " . $transaction['order_number'];

        $items = [];

        $printer = new StickerPrinter($connector);
        foreach ($data as $item) {
            try {
                $printer->setSize(38.1, 31.75, 'mm');
                $printer->text($item, 0, 30, 1, 0, 1);
                $printer->beep();
                $printer->cut();
            } finally {
                $printer->close();
            }
        }
    }
}
