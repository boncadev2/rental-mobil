<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Vehicles/Index', ['vehicles' => Vehicle::query()->with('category')->latest()->paginate(12), 'categories' => VehicleCategory::orderBy('name')->get(['id', 'name'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Vehicles/Form', $this->formData());
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->safe()->except(['feature_ids', 'images', 'delete_image_ids', 'primary_image_id']));
        $this->syncRelations($vehicle, $request);

        return to_route('admin.vehicles.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('Admin/Vehicles/Form', [...$this->formData(), 'vehicle' => $vehicle->load('features', 'images')]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->safe()->except(['feature_ids', 'images', 'delete_image_ids', 'primary_image_id']));
        $this->syncRelations($vehicle, $request);

        return to_route('admin.vehicles.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return to_route('admin.vehicles.index')->with('success', 'Kendaraan berhasil dihapus.');
    }

    private function formData(): array
    {
        return ['categories' => VehicleCategory::orderBy('name')->get(['id', 'name']), 'features' => VehicleFeature::orderBy('name')->get(['id', 'name']), 'statuses' => collect(VehicleStatus::cases())->map(fn ($s) => $s->value)->values()];
    }

    private function syncRelations(Vehicle $vehicle, StoreVehicleRequest $request): void
    {
        $vehicle->features()->sync($request->input('feature_ids', []));
        $deleted = $vehicle->images()->whereKey($request->input('delete_image_ids', []))->get();
        foreach ($deleted as $image) {
            Storage::disk('public')->delete($image->file_path);
            $image->delete();
        } foreach ($request->file('images', []) as $index => $image) {
            $vehicle->images()->create(['file_path' => $image->store('vehicles', 'public'), 'is_primary' => $index === 0 && ! $vehicle->images()->exists(), 'sort_order' => $index]);
        } if ($primaryId = $request->integer('primary_image_id')) {
            $primary = $vehicle->images()->find($primaryId);
            if ($primary) {
                $vehicle->images()->update(['is_primary' => false]);
                $primary->update(['is_primary' => true]);
            }
        }
    }
}
