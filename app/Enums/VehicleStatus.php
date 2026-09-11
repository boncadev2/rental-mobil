<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case RESERVED = 'RESERVED';
    case IN_USE = 'IN_USE';
    case MAINTENANCE = 'MAINTENANCE';
    case INSPECTION = 'INSPECTION';
    case INACTIVE = 'INACTIVE';
}
