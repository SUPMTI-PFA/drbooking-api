<?php

namespace App\Enum;

enum AppointmentStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELED = 'CANCELED';
}
