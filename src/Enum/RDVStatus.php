<?php

namespace App\Enum;

enum RDVStatus: string
{
    case PENDING = "PENDING";
    case CONFIRMED = "CONFIRMED";
    case FINISHED = "FINISHED";
    case CANCELED = "CANCELED";
}
