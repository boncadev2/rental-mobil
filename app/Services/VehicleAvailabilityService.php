<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\CarbonInterface;

class VehicleAvailabilityService
{
    public function isAvailable(Vehicle $vehicle, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $start->lessThan($end) && $this->conflictingBooking($vehicle, $start, $end) === null;
    }

    public function conflictingBooking(Vehicle $vehicle, CarbonInterface $start, CarbonInterface $end): ?Booking
    {
        return Booking::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', [
                BookingStatus::PAID,
                BookingStatus::CONFIRMED,
            ])
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->first();
    }
}
