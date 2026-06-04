<?php

namespace App\Traits;

use App\Enums\CDIS\TerminalTransactionType;
use App\Enums\KDS\OrderType;
use App\Enums\OTS\SourceTransactionType;
use App\Enums\UsageType;
use App\Helpers\IP;
use Illuminate\Support\Carbon;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\UsbPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/* A wrapper to do organise item names & prices into columns */

class MenuItem
{
    private $quantity;
    private $name;
    private $isIndented;
    private $prefix;

    public function __construct($quantity = 1, $name = '', $isIndented = false, $prefix = '')
    {
        $this->quantity = $quantity;
        $this->name = $name;
        $this->isIndented = $isIndented;
        $this->prefix = $prefix;
    }

    public function __toString()
    {
        $rightCols = 38;
        $leftCols = 10;
        if ($this->isIndented) {
            //$leftCols = $leftCols / 2 - $rightCols / 2;
        }
        $left = str_pad($this->quantity, $leftCols);

        $indented = ($this->isIndented ? ('   '.$this->prefix) : '');
        //$right = str_pad($indented . $this->name, $rightCols, ' ', STR_PAD_RIGHT);
        $right = $indented . $this->name;
        return "$left$right\n";
    }
}

/**
 * Trait KitchenPrinterTrait
 * @package App\Traits
 */
trait KitchenPrinterTrait
{
    function printKitchen($printerName, $productItems, $transaction, $cut = true, $openCashdrawer = true)
    {
        /* Information for the receipt */
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);
        $orderType = strtoupper(OrderType::getDescription($transaction['type']));
        $transactionNo = "Transaction #: " . $transaction['transaction_id'];
        $orderNumber = "Order #: " . $transaction['order_number'];

        $items = [];
        foreach ($productItems as $item) {
            $usageType = $item['usage_type'];
            $specialRequest = $item['special_request'];
            
            $prefix = '';
            if ($usageType == UsageType::ADDON) {
                $prefix = '(A)';
            } else if ($usageType == UsageType::BUNDLE) { // This enum stands for MODIFIER
                $prefix = '(MOD)';
            }
            $items[] = new MenuItem(floatToMoney($item['quantity'] ?? 0), $item['name'], $item['is_addon'], $prefix);
            if (! empty($specialRequest)) {
                $items[] = new MenuItem('', "**{$specialRequest}**", $item['is_addon'], '');
            }
        }

        /* Start the printer */
        //$connector = new FilePrintConnector("Dummy");

        $validIPs = IP::extract($printerName);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector($printerName);
        }
        $printer = new Printer($connector);

       // $logo = EscposImage::load("public/storage/logo/hapimoo.png", false);
        /* Print top logo */
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        //$printer->graphics($logo);
        if ($transaction['transaction_type'] != TerminalTransactionType::SALES && $transaction['transaction_type'] != SourceTransactionType::FINEDINE) {
            $this->title($printer, 'PLEASE DO NOT PREPARE');
        }

        $printer->feed(2);
        $printer->selectPrintMode(); // Reset

        /* Title of receipt */
        $printer->setEmphasis(true);
        $printer->text($this->headerLine('', $transactionNo));
        $printer->text($this->headerLine($orderType, $orderNumber));

        $printer->selectPrintMode();

        /* Header Separator */
        $printer->setEmphasis(false);
        $printer->text($headerSeparator);

        /* Items */
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(false);
        foreach ($items as $item) {
            $printer->text($item);
        }

        /* Footer Separator */
        $printer->text($headerSeparator);

        /* Footer */
        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($date . PHP_EOL);

        if ($cut == true) {
            /* Cut the receipt */
            $printer->cut();
        }
        if ($openCashdrawer == true) {
            /* open the cash drawer */
            $printer->pulse();
        }

        $printer->close();
    }

    function title(Printer $printer, $str)
    {
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $printer->text($str);
        $printer->selectPrintMode();
    }

    function headerLine($left, $right, $width = 48)
    {
        $left = str_pad($left, $width - strlen($right)); // Left padding
        return $left . $right; // Concatenate left and right
    }

    /**
     * Print a full kitchen order triggered from KDS.
     * Delegates to printKitchen with the same formatting.
     */
    function printKitchenOrder($printerHost, $productItems, $transaction, $cut = true, $openCashdrawer = true)
    {
        $this->printKitchen($printerHost, $productItems, $transaction, $cut, $openCashdrawer);
    }

    /**
     * Print only the bumped items (moved_quantity > 0 and is_moved = true) from a KDS bump action.
     */
    function printBumpItems($printerHost, $bumpedItems, $transaction, $cut = true, $openCashdrawer = false)
    {
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);
        $transactionNo = "Transaction #: " . ($transaction['transaction_id'] ?? 'N/A');
        $orderNumber   = "Order #: " . ($transaction['order_number'] ?? 'N/A');

        $items = [];
        foreach ($bumpedItems as $item) {
            $movedQty = floatval($item['moved_quantity'] ?? 0);
            if ($movedQty <= 0) {
                continue;
            }
            $isAddon       = (bool) ($item['is_addon'] ?? false);
            $specialRequest = $item['special_request'] ?? '';
            $items[] = new MenuItem(floatToMoney($movedQty), $item['name'], $isAddon, '(BUMP)');
            if (!empty($specialRequest)) {
                $items[] = new MenuItem('', "**{$specialRequest}**", $isAddon, '');
            }
        }

        if (empty($items)) {
            return;
        }

        $validIPs = IP::extract($printerHost);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector($printerHost);
        }
        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->title($printer, 'BUMPED ITEMS');
        $printer->feed(2);
        $printer->selectPrintMode();

        $printer->setEmphasis(true);
        $printer->text($this->headerLine('', $transactionNo) . PHP_EOL);
        $printer->text($this->headerLine('BUMPED', $orderNumber) . PHP_EOL);
        $printer->selectPrintMode();

        $printer->setEmphasis(false);
        $printer->text($headerSeparator . PHP_EOL);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        foreach ($items as $item) {
            $printer->text($item);
        }

        $printer->text($headerSeparator . PHP_EOL);
        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($date . PHP_EOL);

        if ($cut) {
            $printer->cut();
        }
        if ($openCashdrawer) {
            $printer->pulse();
        }
        $printer->close();
    }

    /**
     * Print only the table number in large centered text.
     */
    function printTableNumber($printerHost, $transaction, $cut = true)
    {
        $tableNumber   = $transaction['table_number'] ?? 'N/A';
        $orderNumber   = "Order #: " . ($transaction['order_number'] ?? 'N/A');
        $headerSeparator = str_repeat("-", 48);

        $validIPs = IP::extract($printerHost);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector($printerHost);
        }
        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(1);
        $printer->text($orderNumber . PHP_EOL);
        $printer->text($headerSeparator . PHP_EOL);
        $printer->feed(1);

        // "TABLE NO." label in double size
        $this->title($printer, 'TABLE NO.' . PHP_EOL);

        // Table number value in maximum double-size emphasis
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $printer->setEmphasis(true);
        $printer->text($tableNumber . PHP_EOL);
        $printer->setEmphasis(false);
        $printer->selectPrintMode();

        $printer->feed(2);

        if ($cut) {
            $printer->cut();
        }
        $printer->close();
    }

    /**
     * Print a test receipt to verify printer connectivity and functionality.
     */
    function printerTest($printerHost)
    {
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);

        $validIPs = IP::extract($printerHost);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector($printerHost);
        }
        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(2);
        $this->title($printer, 'PRINTER TEST' . PHP_EOL);
        $printer->feed(1);
        $printer->selectPrintMode();

        $printer->setEmphasis(true);
        $printer->text("Printer Connection Test" . PHP_EOL);
        $printer->selectPrintMode();

        $printer->setEmphasis(false);
        $printer->text($headerSeparator . PHP_EOL);
        $printer->feed(1);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Printer Host: " . $printerHost . PHP_EOL);
        $printer->text("Test Date: " . $date . PHP_EOL);
        $printer->text("Status: OK" . PHP_EOL);

        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($headerSeparator . PHP_EOL);
        $printer->feed(2);
        $printer->text("Printer is working correctly!" . PHP_EOL);
        $printer->feed(2);

        $printer->cut();
        $printer->close();
    }
}
