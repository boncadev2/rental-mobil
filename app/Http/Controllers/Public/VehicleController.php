<?php

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string'],
            'transmission' => ['nullable', 'in:AUTOMATIC,MANUAL'],
            'seats' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $vehicles = Vehicle::query()->with(['category', 'images'])
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->whereHas('category', fn ($q) => $q->where('slug', $category)))
            ->when($filters['transmission'] ?? null, fn ($query, $transmission) => $query->where('transmission', $transmission))
            ->when($filters['seats'] ?? null, fn ($query, $seats) => $query->where('seat_capacity', '>=', $seats))
            ->when(($filters['start_date'] ?? null) && ($filters['end_date'] ?? null), function ($query) use ($filters) {
                $query->with(['bookings' => fn ($bookings) => $bookings
                    ->select(['id', 'vehicle_id', 'start_datetime', 'end_datetime'])
                    ->whereIn('status', [BookingStatus::PAID, BookingStatus::CONFIRMED])
                    ->where('start_datetime', '<', $filters['end_date'].' 23:59:59')
                    ->where('end_datetime', '>', $filters['start_date'].' 00:00:00')]);
            })
            ->orderBy('daily_price')->paginate(12)->withQueryString();

        $vehicles->through(function (Vehicle $vehicle): Vehicle {
            $vehicle->setAttribute('unavailable_booking', $vehicle->relationLoaded('bookings') ? $vehicle->bookings->first() : null);
            $vehicle->unsetRelation('bookings');

            return $vehicle;
        });

        return Inertia::render('Public/Vehicles/Index', ['vehicles' => $vehicles, 'categories' => VehicleCategory::orderBy('name')->get(['name', 'slug']), 'filters' => $filters]);
    }

    public function show(Vehicle $vehicle): Response
    {
        return Inertia::render('Public/Vehicles/Show', ['vehicle' => $vehicle->load(['category', 'images', 'features'])]);
    }
}
