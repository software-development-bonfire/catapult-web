<?php

namespace App\Imports;

use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;

class SampleImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }

    // /**
    //  * @param array $row
    //  *
    //  * @return User|null
    //  */
    // public function model(array $row)
    // {
    //     return new User([
    //        'name'     => $row[0],
    //        'email'    => $row[1], 
    //        'password' => Hash::make($row[2]),
    //     ]);
    // }
}
