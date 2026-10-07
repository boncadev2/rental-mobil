<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    private function types()
    {
        return collect([['name' => 'Ganti Oli', 'interval_km' => 5000, 'interval_days' => 180, 'description' => 'Penggantian oli mesin'], ['name' => 'Service Rutin', 'interval_km' => 10000, 'interval_days' => 180, 'description' => 'Pemeriksaan servis berkala'], ['name' => 'Penggantian Sparepart', 'interval_km' => null, 'interval_days' => null, 'description' => 'Komponen atau sparepart kendaraan']])->map(fn ($type) => MaintenanceType::firstOrCreate(['name' => $type['name']], $type));
    }

    public function index(MaintenanceService $service)
    {
        $this->types();
        $schedules = MaintenanceSchedule::with(['vehicle', 'maintenanceType'])->latest()->get()->map(function ($schedule) use ($service) {
            $schedule->status = $service->status($schedule);

            return $schedule;
        });

        return Inertia::render('Admin/Maintenance/Index', ['vehicles' => Vehicle::query()->withCount('maintenanceRecords')->orderBy('brand')->orderBy('model')->get(['id', 'brand', 'model', 'license_plate', 'current_odometer', 'status']), 'schedules' => $schedules, 'records' => MaintenanceRecord::with(['vehicle', 'type'])->latest('maintenance_date')->paginate(15)]);
    }

    public function show(Vehicle $vehicle, MaintenanceService $service)
    {
        $schedules = $vehicle->maintenanceSchedules()->with('maintenanceType')->get()->map(function ($schedule) use ($service) {
            $schedule->status = $service->status($schedule);

            return $schedule;
        });

        return Inertia::render('Admin/Maintenance/Show', ['vehicle' => $vehicle, 'schedules' => $schedules, 'records' => $vehicle->maintenanceRecords()->with('type')->latest('maintenance_date')->paginate(20)]);
    }

    public function create(Request $request)
    {
        $selectedVehicleId = $request->integer('vehicle');

        return Inertia::render('Admin/Maintenance/Create', ['vehicles' => Vehicle::orderBy('brand')->get(['id', 'brand', 'model', 'license_plate', 'current_odometer']), 'types' => $this->types()->values(), 'selectedVehicleId' => Vehicle::query()->whereKey($selectedVehicleId)->exists() ? $selectedVehicleId : null]);
    }

    public function store(Request $request, MaintenanceService $service)
    {
        $data = $request->validate(['vehicle_id' => 'required|exists:vehicles,id', 'maintenance_type_id' => 'required|exists:maintenance_types,id', 'maintenance_date' => 'required|date', 'odometer' => 'required|integer|min:0', 'workshop' => 'nullable|string|max:255', 'cost' => 'required|numeric|min:0', 'description' => 'nullable|string']);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $type = MaintenanceType::findOrFail($data['maintenance_type_id']);
        $service->record($vehicle, $type, $data, $request->user()->id);

        return redirect()->route('admin.maintenance.show', $vehicle)->with('success','Catatan maintenance berhasil disimpan.');
    }
}
