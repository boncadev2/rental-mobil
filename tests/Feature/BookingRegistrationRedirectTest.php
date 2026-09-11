<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\VehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRegistrationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_customer_registration_before_booking(): void
    {
        $vehicle = $this->availableVehicle();

        $this->get(route('booking.create', $vehicle))
            ->assertRedirect(route('customer-registration.create', ['vehicle' => $vehicle->id]));
    }

    public function test_logged_in_user_can_open_booking_form_before_having_a_customer_profile(): void
    {
        $vehicle = $this->availableVehicle();

        $this->actingAs(User::factory()->create())
            ->get(route('booking.create', $vehicle))
            ->assertOk();
    }

    public function test_only_dates_that_overlap_a_paid_booking_are_unavailable(): void
    {
        $vehicle = $this->availableVehicle();
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-001',
            'full_name' => 'Pelanggan Test',
            'phone' => '08123456789',
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'RNT-20260910-ABC123',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => Carbon::create(2026, 9, 10)->startOfDay(),
            'end_datetime' => Carbon::create(2026, 9, 12)->endOfDay(),
            'pickup_location' => 'Jakarta',
            'rental_days' => 3,
            'base_price' => 1500000,
            'subtotal' => 1500000,
            'total_amount' => 1500000,
            'status' => BookingStatus::WAITING_PAYMENT,
        ]);

        $availability = app(VehicleAvailabilityService::class);

        $this->assertTrue($availability->isAvailable($vehicle, Carbon::create(2026, 9, 10)->startOfDay(), Carbon::create(2026, 9, 12)->endOfDay()));

        $booking->update(['status' => BookingStatus::CONFIRMED]);

        $this->getJson(route('booking.availability', ['vehicle' => $vehicle, 'start_date' => '2026-09-10', 'end_date' => '2026-09-12']))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('booking.start_datetime', '2026-09-10T00:00:00.000000Z');

        $this->assertTrue($availability->isAvailable($vehicle, Carbon::create(2026, 9, 8)->startOfDay(), Carbon::create(2026, 9, 9)->endOfDay()));
        $this->assertFalse($availability->isAvailable($vehicle, Carbon::create(2026, 9, 10)->startOfDay(), Carbon::create(2026, 9, 12)->endOfDay()));
        $this->assertTrue($availability->isAvailable($vehicle, Carbon::create(2026, 9, 13)->startOfDay(), Carbon::create(2026, 9, 14)->endOfDay()));
    }

    public function test_customer_only_sees_their_own_booking_history(): void
    {
        $vehicle = $this->availableVehicle();
        $customer = Customer::query()->create([
            'user_id' => User::factory()->create()->id,
            'customer_code' => 'CUS-OWNER',
            'full_name' => 'Pelanggan Pemilik',
            'phone' => '08120000001',
        ]);
        $otherCustomer = Customer::query()->create([
            'user_id' => User::factory()->create()->id,
            'customer_code' => 'CUS-OTHER',
            'full_name' => 'Pelanggan Lain',
            'phone' => '08120000002',
        ]);

        $this->bookingFor($customer, $vehicle, 'RNT-MILIK-001');
        $this->bookingFor($otherCustomer, $vehicle, 'RNT-LAIN-001');

        $this->actingAs($customer->user)
            ->get(route('my-bookings.index'))
            ->assertOk()
            ->assertSee('RNT-MILIK-001')
            ->assertDontSee('RNT-LAIN-001');
    }

    public function test_profile_loads_customer_verification_data_from_a_matching_customer_record(): void
    {
        $user = User::factory()->create(['email' => 'pelanggan@example.com']);
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-PROFILE',
            'full_name' => 'Pelanggan Profil',
            'email' => $user->email,
            'phone' => '08120000003',
            'status' => 'VERIFIED',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();

        $this->assertSame($user->id, $customer->fresh()->user_id);
    }

    private function bookingFor(Customer $customer, Vehicle $vehicle, string $code): Booking
    {
        return Booking::query()->create([
            'booking_code' => $code,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => now()->addDay()->startOfDay(),
            'end_datetime' => now()->addDays(2)->endOfDay(),
            'pickup_location' => 'Jakarta',
            'rental_days' => 2,
            'base_price' => 1000000,
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'status' => BookingStatus::WAITING_PAYMENT,
        ]);
    }

    private function availableVehicle(): Vehicle
    {
        $category = VehicleCategory::query()->create(['name' => 'MPV', 'slug' => 'mpv']);

        return Vehicle::query()->create([
            'vehicle_category_id' => $category->id,
            'code' => 'CAR-001',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'year' => 2024,
            'license_plate' => 'B 1234 ABC',
            'color' => 'Hitam',
            'transmission' => 'AT',
            'fuel_type' => 'Bensin',
            'seat_capacity' => 7,
            'daily_price' => 500000,
            'status' => 'AVAILABLE',
        ]);
    }
}
