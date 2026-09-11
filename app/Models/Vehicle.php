<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['vehicle_category_id', 'code', 'brand', 'model', 'variant', 'year', 'license_plate', 'color', 'transmission', 'fuel_type', 'seat_capacity', 'daily_price', 'hourly_price', 'driver_daily_price', 'security_deposit', 'current_odometer', 'fuel_capacity', 'status', 'registration_number', 'stnk_expired_at', 'tax_expired_at', 'description', 'featured'];

    protected function casts(): array
    {
        return ['status' => VehicleStatus::class, 'featured' => 'boolean', 'stnk_expired_at' => 'date', 'tax_expired_at' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(VehicleFeature::class, 'vehicle_feature_pivot');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }
}
