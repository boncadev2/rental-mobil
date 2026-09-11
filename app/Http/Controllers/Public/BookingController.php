<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\VehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(Request $request, Vehicle $vehicle): Response|RedirectResponse
    {
        if ($request->user() === null) {
            return redirect()->route('customer-registration.create', ['vehicle' => $vehicle->id]);
        }

        $customer = $request->user()->customer;

        return Inertia::render('Public/Booking/Create', [
            'vehicle' => $vehicle,
            'customerVerified' => $customer?->status === 'VERIFIED',
            'customerPhone' => $customer?->phone,
        ]);
    }

    public function availability(Request $request, Vehicle $vehicle, VehicleAvailabilityService $availability): JsonResponse
    {
        $dates = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $booking = $availability->conflictingBooking(
            $vehicle,
            Carbon::parse($dates['start_date'])->startOfDay(),
            Carbon::parse($dates['end_date'])->endOfDay(),
        );

        return response()->json([
            'available' => $booking === null,
            'booking' => $booking?->only(['start_datetime', 'end_datetime']),
        ]);
    }

    public function store(StoreBookingRequest $request, BookingService $service): JsonResponse
    {
        $data = $request->validated();
        $booking = $service->create([...$data, 'user_id' => $request->user()->id]);

        return response()->json([
            'booking_code' => $booking->booking_code,
            'status' => $booking->status->value,
            'total_amount' => $booking->total_amount,
            'payment_url' => route('payment.show', ['booking' => $booking, 'payment_type' => $data['payment_type']]),
        ], 201);
    }
}
