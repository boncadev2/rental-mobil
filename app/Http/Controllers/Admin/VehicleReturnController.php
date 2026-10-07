<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\VehicleCheckout;
use App\Services\VehicleReturnService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleReturnController extends Controller
{
    public function create(Booking $booking)
    {
        return Inertia::render('Admin/Returns/Create', [
            'booking' => $booking->load(['vehicle', 'customer']),
            'checkout' => VehicleCheckout::query()->with('items')->where('booking_id', $booking->id)->first(),
        ]);
    }

    public function store(Request $request, Booking $booking, VehicleReturnService $service)
    {
        $data = $request->validate([
            'odometer' => 'required|integer|min:0', 'fuel_level' => 'required|integer|min:0|max:100',
            'damage_fee' => 'nullable|integer|min:0', 'fuel_fee' => 'nullable|integer|min:0',
            'cleaning_fee' => 'nullable|integer|min:0', 'notes' => 'nullable|string', 'items' => 'nullable|array',
            'items.*.checklist_code' => 'required_with:items|string|max:255',
            'items.*.checklist_name' => 'required_with:items|string|max:255',
            'items.*.condition_status' => 'required_with:items|in:GOOD,DAMAGED,MISSING',
            'items.*.notes' => 'nullable|string',
        ]);
        $service->process($booking, $data, $request->user()->id);
        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Check-in pengembalian berhasil disimpan.');
    }
}
