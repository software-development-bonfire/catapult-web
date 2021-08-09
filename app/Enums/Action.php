<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Action extends Enum
{
    const VIEW = "View";
    const CREATE = "Create";
    const UPDATE = "Update";
    const DELETE = "Delete";
    const DOWNLOAD = "Download";
    const UPLOAD = "Upload";
    const SYCING = "Syncing";
    const CONVERSION = "Conversion";

}
