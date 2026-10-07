<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private VehicleAvailabilityService $availability, private BookingPriceService $prices) {}

    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);
            if (! $this->availability->isAvailable($vehicle, $start, $end)) {
                throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan tidak tersedia pada periode tersebut.']);
            }

            $customer = Customer::query()->where('user_id', $data['user_id'])->first();
            if ($customer === null) {
                $customer = Customer::query()->create([
                    'user_id' => $data['user_id'],
                    'customer_code' => 'CUS-'.str()->upper(str()->random(8)),
                    'full_name' => $data['full_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null,
                    'status' => 'ACTIVE',
                ]);
            }

            $update = ['full_name' => $data['full_name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone']];
            if (isset($data['ktp_file'])) {
                $update['ktp_file_path'] = Storage::disk('local')->putFile('identity-cards', $data['ktp_file']);
                $update['ktp_verified_at'] = now();
                $update['status'] = 'VERIFIED';
            }

            $customer->update($update);
            $price = $this->prices->calculate($vehicle, $start, $end, (bool) ($data['use_driver'] ?? false), $data['driver_type'] ?? null);

            return Booking::create([...$price, 'booking_code' => 'RNT-'.now()->format('Ymd').'-'.str()->upper(str()->random(6)), 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'start_datetime' => $start, 'end_datetime' => $end, 'pickup_location' => $data['pickup_location'], 'return_location' => $data['return_location'] ?? null, 'use_driver' => $data['use_driver'] ?? false, 'driver_type' => $data['driver_type'] ?? null, 'status' => BookingStatus::WAITING_PAYMENT, 'payment_expires_at' => now()->addMinutes(30), 'customer_notes' => $data['customer_notes'] ?? null]);
        });
    }
}
