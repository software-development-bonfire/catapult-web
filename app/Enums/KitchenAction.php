<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class KitchenAction extends Enum
{
    const MoveItem =        1;
    const MoveMenu =        2;
    const MoveOrder =       3;

    const DoneItem =        4;
    const DoneMenu =        5;
    const DoneOrder =       6;

    const ReleaseItem =     7;
    const ReleaseMenu =     8;
    const ReleaseOrder =     9;
    

    const RemoveItem =      10;
    const RemoveMenu =      11;
    const RemoveOrder =     12;
}
