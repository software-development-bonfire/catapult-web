<?php

namespace App\Services;

use App\Entities\TerminalFileSetup;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TerminalFileSetupService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @return TerminalFileSetup
     */
    public function store($data)
    {
        return $this->transaction(function () use ($data) {
            $data['created_by'] = Auth::user()->bid;
            if ($data['endpoint']) {
                $data['api_setup_bid'] = $data['endpoint']['value'];
            };

            $terminalFileSetup = TerminalFileSetup::create($data);

            return $terminalFileSetup;
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @param string  $bid
     * @return TerminalFileSetup
     */
    public function update($data, $bid)
    {
        return $this->transaction(function () use ($data, $bid) {
            $data['updated_by'] = Auth::user()->bid;
            if ($data['endpoint']) {
                $data['api_setup_bid'] = $data['endpoint']['value'];
            };

            $terminalFileSetup = TerminalFileSetup::find($bid)->update($data);

            return $terminalFileSetup;
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return TerminalFileSetup
     */
    public function destroy($bid)
    {
        return $this->transaction(function () use ($bid) {
            return TerminalFileSetup::find($bid)->delete();
        });
    }
}
