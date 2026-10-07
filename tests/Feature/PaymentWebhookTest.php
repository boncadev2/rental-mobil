<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_replay_is_idempotent(): void
    {
        $category = VehicleCategory::create(['name' => 'MPV', 'slug' => 'mpv']);
        $vehicle = Vehicle::create(['vehicle_category_id' => $category->id, 'code' => 'AVZ-01', 'brand' => 'Toyota', 'model' => 'Avanza', 'year' => 2025, 'license_plate' => 'B 1234 ABC', 'color' => 'Black', 'transmission' => 'AUTOMATIC', 'fuel_type' => 'GASOLINE', 'seat_capacity' => 7, 'daily_price' => 500000, 'driver_daily_price' => 0, 'security_deposit' => 100000, 'current_odometer' => 0, 'status' => 'AVAILABLE']);
        $customer = Customer::create(['customer_code' => 'CUS-001', 'full_name' => 'Andi', 'phone' => '08123456789']);
        $booking = Booking::create(['booking_code' => 'RNT-001', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDays(2), 'pickup_location' => 'Jakarta', 'return_location' => 'Jakarta', 'rental_days' => 1, 'base_price' => 500000, 'subtotal' => 500000, 'total_amount' => 600000, 'status' => 'WAITING_PAYMENT']);
        $payment = app(PaymentService::class)->create($booking, 'QRIS');
        $payload = ['payment_code' => $payment->payment_code, 'transaction_id' => 'gateway-123', 'status' => 'PAID'];

        app(PaymentService::class)->webhook($payload);
        app(PaymentService::class)->webhook($payload);

        $this->assertSame(1, Payment::where('gateway_transaction_id', 'gateway-123')->count());
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('CONFIRMED', $booking->fresh()->status->value);
    }
}
