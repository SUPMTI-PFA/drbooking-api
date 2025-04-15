<?php

namespace App\Enum;

enum AccountType: string
{
    case USER = "USER";
    case ADMIN = "ADMIN";
    case PATIENT = "PATIENT";
    case DOCTOR = "DOCTOR";
}
