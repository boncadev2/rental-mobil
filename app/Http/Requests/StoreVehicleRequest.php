<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['vehicle_category_id' => ['required', 'exists:vehicle_categories,id'], 'code' => ['required', 'string', 'max:50', 'unique:vehicles,code'], 'brand' => ['required', 'string', 'max:100'], 'model' => ['required', 'string', 'max:100'], 'variant' => ['nullable', 'string', 'max:100'], 'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)], 'license_plate' => ['required', 'string', 'max:30', 'unique:vehicles,license_plate'], 'color' => ['required', 'string', 'max:50'], 'transmission' => ['required', 'in:MANUAL,AUTOMATIC'], 'fuel_type' => ['required', 'in:GASOLINE,DIESEL,ELECTRIC,HYBRID'], 'seat_capacity' => ['required', 'integer', 'min:1', 'max:30'], 'daily_price' => ['required', 'numeric', 'min:0'], 'hourly_price' => ['nullable', 'numeric', 'min:0'], 'driver_daily_price' => ['required', 'numeric', 'min:0'], 'security_deposit' => ['required', 'numeric', 'min:0'], 'current_odometer' => ['required', 'integer', 'min:0'], 'fuel_capacity' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', Rule::enum(VehicleStatus::class)], 'description' => ['nullable', 'string'], 'featured' => ['boolean'], 'feature_ids' => ['array'], 'feature_ids.*' => ['integer', 'exists:vehicle_features,id'], 'images' => ['array', 'max:8'], 'images.*' => ['image', 'max:5120'], 'delete_image_ids' => ['array'], 'delete_image_ids.*' => ['integer', 'exists:vehicle_images,id'], 'primary_image_id' => ['nullable', 'integer', 'exists:vehicle_images,id']];
    }
}
