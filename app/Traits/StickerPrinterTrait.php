<?php

namespace App\Traits;

use App\Enums\KDS\OrderType;
use App\Helpers\IP;
use App\Helpers\StickerLabel\PrintImages\TsplImage;
use App\Helpers\StickerLabel\StickerPrinterss;
use App\Http\Requests\KitchenPrinterRequest;
use App\Repositories\Contracts\KitchenPrinterRepository;
use Illuminate\Support\Carbon;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Trait StickerPrinterTrait
 * @package App\Traits
 */
trait StickerPrinterTrait
{
     /**
     * Get Catapult key
     *
     * @return string
     */
    public function getConfigStickerPrinter()
    {
        return config()->get('system.printers.sticker');
    }


    function printSticker($printerName, $data, $transaction, $cut = true, $openCashdrawer = true)
    {
        /* Start the printer */
        $validIPs = IP::extract($printerName);
        if (isset($validIPs[0]) && IP::validate($validIPs[0])) {
            $connector = new NetworkPrintConnector($validIPs[0], 9100);
        } else {
            $connector = new WindowsPrintConnector($this->getConfigStickerPrinter());
        }

        /* Information for the receipt */
        $date = parseDateTime(Carbon::now(), 'l jS \of F Y h:i:s A');
        $headerSeparator = str_repeat("-", 48);
        $orderNumber =  "#". $transaction['order_number'];

        $request = '';
        $items = [];

        $printer = new StickerPrinterss($connector);

        $index = 1;
        $totalCount = count($data);
        foreach ($data as $item) {
            try {
                $expectedY = 220;
                $printer->setup('mm', 38.1, 31.75);
                $y = 30;
                $printer->text($orderNumber, 2, $y, 1, 0, 1);
                $printer->text("{$index}/{$totalCount}", 250, $y, 1, 0, 1);

                $itemSize = 20;
                $requestSize = 26;
                // Split the text into chunks of 10 characters
                $chunks = str_split($item['name'], $itemSize);
                $y = $y + 30;
                // Print the chunks
                foreach ($chunks as $chunk) {
                    $printer->text($chunk, 2, $y, 2, 0, 1);
                    $y = $y + 20;
                }

                if (isset($item['addons']) && count($item['addons']) > 0) {
                    foreach ($item['addons'] as $addon) {
                        $productPackaging = app()->make(KitchenPrinterRepository::class)->getProductIsPrintSticker($addon['product_bid']);
                        if($productPackaging && (isset($productPackaging->is_print_sticker) && $productPackaging->is_print_sticker == 1)) {
                            $y = $y + 20;
                            $printer->text('    ' . $addon, 2, $y, 6, 0, 1);

                            if ($y >= $expectedY) { // If the addons exceed on the print label
                                // Print the 
                                $printer->print();
                                $y = 30; //reset the Y
                            }
                        }
                    }
                }

                if ($y >= $expectedY) { // Still the addons exceed on the print label
                    // Print the 
                    $printer->print();
                    $y = 30; //reset the Y
                }

                $y = $y + 30;
                /* This section is for REQUEST */
                $requestChunks = str_split($request, $requestSize);
                // Print the chunks
                foreach ($requestChunks as $chunk) {
                    if (!empty(trim($chunk))) {
                        $printer->text($chunk, 2, $y, 1, 0, 1);
                        $y = $y + 20;
                        if ($y >= $expectedY) { // If the request exceed
                            // Print the 
                            $printer->print();
                            $y = 30; //reset the Y
                        }
                    }
                }

               
                $y = $y + 30;
                //$printer->qrcode('Bonfire', 210, $y - 40);

                $printer->text('TO: WALKIN', 2, 180, 1, 0, 1);
                
                $y = $y + 30;
                $printer->text('DINE-IN', 2, 200, 1, 0, 1);
                $y = $y + 30;
                $printer->text(now(), 2, 220, 1, 0, 1);
                $printer->print();
            } finally {
                $printer->close();
            }
            $index++;
        }
    }

    function printSeparate()
    {
        //$printer->text('    ' . $addon, 2, $y, 6, 0, 1);
    }
}
