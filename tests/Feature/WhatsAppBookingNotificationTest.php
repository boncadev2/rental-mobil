<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\WhatsAppBookingNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppBookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_booking_notifications_to_customer_and_admin(): void
    {
        config()->set('services.whatsapp', [
            'base_url' => 'https://wa.gadstudio.cloud',
            'api_key' => 'test-api-key',
            'admin_phone' => '081200000000',
        ]);

        Http::fake();

        app(WhatsAppBookingNotificationService::class)->sendBookingCreated($this->booking());

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://wa.gadstudio.cloud/api/send-text'
                && $request->hasHeader('x-api-key', 'test-api-key')
                && $request->data()['to'] === '628123456789'
                && str_contains($request->data()['message'], 'Booking Anda berhasil dibuat');
        });
        Http::assertSent(function ($request) {
            return $request->data()['to'] === '6281200000000'
                && str_contains($request->data()['message'], 'Booking baru berhasil masuk');
        });
    }

    private function booking(): Booking
    {
        $category = VehicleCategory::query()->create(['name' => 'MPV', 'slug' => 'mpv']);
        $vehicle = Vehicle::query()->create(['vehicle_category_id' => $category->id, 'code' => 'AVZ-01', 'brand' => 'Toyota', 'model' => 'Avanza', 'year' => 2025, 'license_plate' => 'B 1234 ABC', 'color' => 'Hitam', 'transmission' => 'AUTOMATIC', 'fuel_type' => 'GASOLINE', 'seat_capacity' => 7, 'daily_price' => 500000, 'status' => 'AVAILABLE']);
        $customer = Customer::query()->create(['customer_code' => 'CUS-001', 'full_name' => 'Andi', 'phone' => '08123456789']);

        return Booking::query()->create(['booking_code' => 'RNT-001', 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDays(2), 'pickup_location' => 'Jakarta', 'rental_days' => 2, 'base_price' => 1000000, 'subtotal' => 1000000, 'total_amount' => 1000000, 'status' => 'WAITING_PAYMENT']);
    }
}
