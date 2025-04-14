<?php

namespace App\Enum;

enum EnvEnum: string
{
    //% New Company Through Admin Dash
    case ADMIN_COMPANY = 'ADMIN_COMPANY'; //%Form Data with Prifix: company
        // company->
        //         data
        //         user[data]
        //         companyDocument[data]
        // env

    //% New Company + Driver Through Driver App
    case DRIVER_COMPANY = 'DRIVER_COMPANY';  //% Form Data with: Prifix company | Profix user
        // user->
        //       data
        //       driver[data]
        //       userDocument[data]
        // company->
        //         data
        //         user[data]
        //         companyDocument[data]
        // env

    //% New Driver/Passenger Through Driver App | passenger App | Admin dash
    case DRIVER_OR_PASSENGER = 'DRIVER_OR_PASSENGER'; //% Old Form Data Structure
        //data->
        //      driver[data]
        //      userDocument[data]
        // env

        //% OR

        //data->
        //      passenger[data]
        // env
}
