<?php

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\VehicleCheckout;
use App\Models\VehicleReturn;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MyBookingController extends Controller
{
    public function index(): Response
    {
        $customerId = request()->user()->customer?->id;

        return Inertia::render('Customer/Bookings/Index', ['bookings' => Booking::query()
            ->with(['vehicle.category', 'invoice'])
            ->where('customer_id', $customerId)
            ->latest()->paginate(12)]);
    }

    public function show(Booking $booking): Response
    {
        abort_unless($booking->customer?->user_id === request()->user()->id, 403);

        return Inertia::render('Customer/Bookings/Show', ['booking' => $booking->load(['vehicle.category', 'vehicle.features', 'vehicle.images', 'customer', 'invoice']), 'checkout' => VehicleCheckout::query()->with('items')->where('booking_id', $booking->id)->first(), 'checkin' => VehicleReturn::query()->with('items')->where('booking_id', $booking->id)->first()]);
    }

    public function requestReturn(Booking $booking): RedirectResponse
    {
        abort_unless($booking->customer?->user_id === request()->user()->id, 403);
        abort_unless($booking->status === BookingStatus::IN_USE, 422);
        $booking->update(['status' => BookingStatus::RETURN_INSPECTION]);
        $booking->vehicle()->update(['status' => VehicleStatus::INSPECTION]);

        return redirect()->route('my-bookings.show', $booking)->with('success', 'Pengembalian diajukan. Admin akan melakukan pemeriksaan kendaraan.');
    }
}
