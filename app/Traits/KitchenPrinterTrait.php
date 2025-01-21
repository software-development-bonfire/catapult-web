<?php

namespace App\Traits;

use App\Enums\KDS\OrderType;
use App\Helpers\IP;
use Illuminate\Support\Carbon;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\UsbPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/* A wrapper to do organise item names & prices into columns */

class item
{
    private $quantity;
    private $name;
    private $isIndented;

    public function __construct($quantity = 1, $name = '', $isIndented = false)
    {
        $this->quantity = $quantity;
        $this->name = $name;
        $this->isIndented = $isIndented;
    }

    public function __toString()
    {
        $rightCols = 38;
        $leftCols = 10;
        if ($this->isIndented) {
            //$leftCols = $leftCols / 2 - $rightCols / 2;
        }
        $left = str_pad($this->quantity, $leftCols);

        $indented = ($this->isIndented ? '    ' : '');
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
    function printKitchen($printerName, $data, $transaction, $cut = true, $openCashdrawer = true)
    {
        /* Information for the receipt */
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);
        $orderType = strtoupper(OrderType::getDescription($transaction['type']));
        $orderNumber = "Order #: " . $transaction['order_number'];

        $items = [];
        foreach ($data as $item) {
            $items[] = new Item(floatToMoney($item['quantity']), $item['name'], $item['is_addon']);
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

        //$logo = EscposImage::load("public/storage/logo/hapimoo.png", false);
        /* Print top logo */
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        //$printer->graphics($logo);

        $printer->feed();

        /* Title of receipt */
        $printer->setEmphasis(true);
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

    function printQRCode($networkIPAddress)
    {
        $connector = new NetworkPrintConnector($networkIPAddress, 9100);
        $printer = new Printer($connector);

        // Most simple example
        $this->title($printer, "QR code demo\n");
        $testStr = "Testing 123";
        $printer->qrCode($testStr);
        $printer->text("Most simple example\n");
        $printer->feed();

        // Demo that alignment is the same as text
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->qrCode($testStr);
        $printer->text("Same example, centred\n");
        $printer->setJustification();
        $printer->feed();

        // Demo of numeric data being packed more densly
        $this->title($printer, "Data encoding\n");
        $test = array(
            "Numeric"      => "0123456789012345678901234567890123456789",
            "Alphanumeric" => "abcdefghijklmnopqrstuvwxyzabcdefghijklmn",
            "Binary"       => str_repeat("\0", 40)
        );
        foreach ($test as $type => $data) {
            $printer->qrCode($data);
            $printer->text("$type\n");
            $printer->feed();
        }

        // Demo of error correction
        $this->title($printer, "Error correction\n");
        $ec = array(
            Printer::QR_ECLEVEL_L => "L",
            Printer::QR_ECLEVEL_M => "M",
            Printer::QR_ECLEVEL_Q => "Q",
            Printer::QR_ECLEVEL_H => "H"
        );
        foreach ($ec as $level => $name) {
            $printer->qrCode($testStr, $level);
            $printer->text("Error correction $name\n");
            $printer->feed();
        }

        // Change size
        $this->title($printer, "Pixel size\n");
        $sizes = array(
            1 => "(minimum)",
            2 => "",
            3 => "(default)",
            4 => "",
            5 => "",
            10 => "",
            16 => "(maximum)"
        );
        foreach ($sizes as $size => $label) {
            $printer->qrCode($testStr, Printer::QR_ECLEVEL_L, $size);
            $printer->text("Pixel size $size $label\n");
            $printer->feed();
        }

        // Change model
        $this->title($printer, "QR model\n");
        $models = array(
            Printer::QR_MODEL_1 => "QR Model 1",
            Printer::QR_MODEL_2 => "QR Model 2 (default)",
            Printer::QR_MICRO => "Micro QR code\n(not supported on all printers)"
        );
        foreach ($models as $model => $name) {
            $printer->qrCode($testStr, Printer::QR_ECLEVEL_L, 3, $model);
            $printer->text("$name\n");
            $printer->feed();
        }

        // Cut & close
        $printer->cut();
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
}
