<?php

namespace App\Services;

use App\Entities\CDISKitchenDevicePrinter;
use App\Helpers\IP;
use App\Traits\KitchenPrinterTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class KitchenDevicePrinterService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  string  $bid
     */
    public function update($data, $bid)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::user()->bid;

            CDISKitchenDevicePrinter::find($bid)->update($data);

            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

    /**
     * Send a test print to the specified printer.
     *
     * @param  string  $bid
     */
    public function printTest($bid)
    {
        $printer = CDISKitchenDevicePrinter::find($bid);
        if (!$printer) {
            throw new \Exception('Printer not found');
        }

        $printerHost = $printer->local_printer ?? $printer->printer_host;
        if (!$printerHost) {
            throw new \Exception('Printer host configuration not found');
        }

        return $printerHost;
        //$this->executePrintTest($printerHost);
    }

    /**
     * Execute a test print using the provided printer host.
     *
     * @param  string  $printerHost
     */
    private function executePrintTest($printerHost)
    {
        $date = \Carbon\Carbon::now()->format('l jS \\of F Y h:i:s A');
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
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $printer->text('PRINTER TEST' . PHP_EOL);
        $printer->selectPrintMode();
        
        $printer->feed(1);
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
