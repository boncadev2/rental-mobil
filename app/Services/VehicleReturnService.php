<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\VehicleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleReturnService
{
    private const LATE_GRACE_MINUTES = 60;
    private const LATE_FEE_PER_HOUR = 50000;

    public function process(Booking $booking, array $data, int $userId): VehicleReturn
    {
        return DB::transaction(function () use ($booking, $data, $userId) {
            $booking = Booking::query()->lockForUpdate()->with('vehicle')->findOrFail($booking->id);

            if (! in_array($booking->status, [BookingStatus::IN_USE, BookingStatus::RETURN_INSPECTION], true)) {
                throw ValidationException::withMessages(['booking' => 'Booking tidak sedang digunakan.']);
            }
            if ($data['odometer'] < $booking->vehicle->current_odometer) {
                throw ValidationException::withMessages(['odometer' => 'Odometer return tidak boleh lebih rendah.']);
            }

            $lateMinutes = max(0, now()->diffInMinutes($booking->end_datetime, false) * -1);
            $billableMinutes = max(0, $lateMinutes - self::LATE_GRACE_MINUTES);
            $lateFee = (int) ceil($billableMinutes / 60) * self::LATE_FEE_PER_HOUR;
            $totalCharge = $lateFee + ($data['damage_fee'] ?? 0) + ($data['fuel_fee'] ?? 0) + ($data['cleaning_fee'] ?? 0);
            $returnData = collect($data)->except('items')->all();

            $return = VehicleReturn::create([
                ...$returnData,
                'booking_id' => $booking->id,
                'vehicle_id' => $booking->vehicle_id,
                'checked_by' => $userId,
                'return_datetime' => now(),
                'late_minutes' => $lateMinutes,
                'late_fee' => $lateFee,
                'total_additional_charge' => $totalCharge,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $return->items()->create($item);
            }

            $booking->vehicle->update(['current_odometer' => $data['odometer'], 'status' => VehicleStatus::AVAILABLE]);
            $booking->update(['status' => $totalCharge > 0 ? BookingStatus::RETURN_INSPECTION : BookingStatus::COMPLETED]);

            return $return;
        });
    }
}
